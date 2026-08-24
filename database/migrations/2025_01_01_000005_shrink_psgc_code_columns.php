<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shrink the PSGC code columns from varchar(255) to varchar(20).
 *
 * 2.0 bounded these in the create migrations, which only helped fresh installs.
 * This brings tables created by 1.x into line.
 *
 * Under utf8mb4 an unbounded varchar(255) is 1020 bytes per column, which put
 * the cities composite index (region_code, province_code, name) at 3060 bytes
 * against InnoDB's 3072-byte key limit. Afterwards that index is 1180 bytes.
 *
 * Uses Laravel's native ->change(); doctrine/dbal has not been needed since
 * Laravel 11, and 2.0 requires 12+.
 */
return new class extends Migration
{
    private const LENGTH = 20;

    /**
     * Column => whether it is nullable.
     *
     * ->change() restates the whole column definition, so nullability has to be
     * repeated here. Getting it wrong would turn a nullable column NOT NULL and
     * fail against existing NULLs -- cities.province_code is NULL for every
     * HUC/ICC in the dataset.
     *
     * @return array<string, array<string, bool>>
     */
    private function schema(): array
    {
        return [
            (string) config('psgc.tables.regions', 'regions') => [
                'code' => false,
            ],
            (string) config('psgc.tables.provinces', 'provinces') => [
                'code'        => false,
                'region_code' => false,
            ],
            (string) config('psgc.tables.cities', 'cities') => [
                'code'          => false,
                'region_code'   => false,
                'province_code' => true,
                'city_class'    => true,
            ],
            (string) config('psgc.tables.barangays', 'barangays') => [
                'code'      => false,
                'city_code' => false,
            ],
        ];
    }

    public function up(): void
    {
        if (! $this->supported()) {
            return;
        }

        foreach ($this->schema() as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $pending = [];

            foreach ($columns as $column => $nullable) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                // Skip anything already the right width, so a fresh install does
                // not pay for a table rebuild it does not need.
                if ($this->currentLength($table, $column) === self::LENGTH) {
                    continue;
                }

                $this->guardAgainstOversizedValues($table, $column);

                $pending[$column] = $nullable;
            }

            if ($pending === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($pending): void {
                foreach ($pending as $column => $nullable) {
                    $definition = $blueprint->string($column, self::LENGTH);

                    if ($nullable) {
                        $definition->nullable();
                    }

                    $definition->change();
                }
            });
        }
    }

    public function down(): void
    {
        if (! $this->supported()) {
            return;
        }

        foreach ($this->schema() as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $column => $nullable) {
                    $definition = $blueprint->string($column, 255);

                    if ($nullable) {
                        $definition->nullable();
                    }

                    $definition->change();
                }
            });
        }
    }

    /**
     * SQLite does not enforce VARCHAR lengths, so narrowing them there buys
     * nothing and costs a full table rebuild. Other drivers are left alone
     * rather than guessed at.
     */
    private function supported(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb', 'pgsql'], true);
    }

    private function currentLength(string $table, string $column): ?int
    {
        $scope = DB::connection()->getDriverName() === 'pgsql' ? 'current_schema()' : 'database()';

        $length = DB::selectOne(
            "SELECT character_maximum_length AS len
               FROM information_schema.columns
              WHERE table_schema = {$scope}
                AND table_name = ?
                AND column_name = ?",
            [$table, $column]
        )?->len;

        return $length === null ? null : (int) $length;
    }

    /**
     * Refuse to truncate. MySQL in non-strict mode would silently cut values
     * short, which for a primary key means losing rows to a duplicate key.
     */
    private function guardAgainstOversizedValues(string $table, string $column): void
    {
        $longest = (int) DB::table($table)
            ->whereNotNull($column)
            ->selectRaw("MAX(LENGTH({$column})) AS len")
            ->value('len');

        if ($longest > self::LENGTH) {
            throw new RuntimeException(
                "Cannot shrink {$table}.{$column} to varchar(" . self::LENGTH . "): "
                . "it holds a value {$longest} characters long. Shorten or remove that data first. "
                . 'PSGC codes are at most 10 characters, so this suggests non-PSGC data in the table.'
            );
        }
    }
};
