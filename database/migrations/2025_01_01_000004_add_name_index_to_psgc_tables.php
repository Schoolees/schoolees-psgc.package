<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `name` is the default sort column (config `psgc.order_by`), but on these tables
 * it only existed as the trailing column of a composite index, which a bare
 * `ORDER BY name` cannot use. Without this, every unfiltered listing full-scans
 * and filesorts the table — ~42k rows for barangays.
 *
 * Added as a separate migration rather than edited into the create migrations so
 * that installs which already ran those also pick the indexes up.
 */
return new class extends Migration {
    /**
     * @return array<int, string>
     */
    private function tables(): array
    {
        return [
            (string) config('psgc.tables.provinces', 'provinces'),
            (string) config('psgc.tables.cities', 'cities'),
            (string) config('psgc.tables.barangays', 'barangays'),
        ];
    }

    public function up(): void
    {
        foreach ($this->tables() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->index('name', "{$table}_name_index");
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropIndex("{$table}_name_index");
            });
        }
    }
};
