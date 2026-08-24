<?php

namespace Schoolees\Psgc\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Schoolees\Psgc\Models\City;
use Schoolees\Psgc\Tests\TestCase;

/**
 * The shrink migration only does anything on MySQL/PostgreSQL, and only
 * INFORMATION_SCHEMA can prove it worked.
 */
class ShrinkCodeColumnsMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->onMysql()) {
            $this->markTestSkipped('VARCHAR lengths are not enforced by SQLite.');
        }
    }

    private function migration(): object
    {
        // require, not require_once: each call needs a fresh instance.
        return require __DIR__ . '/../../database/migrations/2025_01_01_000005_shrink_psgc_code_columns.php';
    }

    private function widen(string $table, string $column, string $null = 'NOT NULL'): void
    {
        DB::statement("ALTER TABLE {$table} MODIFY {$column} VARCHAR(255) {$null}");
    }

    private function lengthOf(string $table, string $column): ?int
    {
        return DB::selectOne(
            'SELECT CHARACTER_MAXIMUM_LENGTH AS len FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        )?->len;
    }

    private function isNullable(string $table, string $column): bool
    {
        return DB::selectOne(
            'SELECT IS_NULLABLE AS n FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        )?->n === 'YES';
    }

    public function test_it_shrinks_columns_left_over_from_1_x(): void
    {
        $this->widen('cities', 'code');
        $this->widen('cities', 'region_code');
        $this->widen('cities', 'province_code', 'NULL');
        $this->widen('cities', 'city_class', 'NULL');

        $this->assertSame(255, (int) $this->lengthOf('cities', 'code'));

        $this->migration()->up();

        foreach (['code', 'region_code', 'province_code', 'city_class'] as $column) {
            $this->assertSame(20, (int) $this->lengthOf('cities', $column), "cities.{$column}");
        }
    }

    /**
     * ->change() restates the whole definition, so a missed ->nullable() would
     * turn the column NOT NULL. province_code is NULL for every HUC/ICC.
     */
    public function test_it_preserves_nullability_and_existing_null_rows(): void
    {
        City::query()->create([
            'code' => '013301000', 'name' => 'Dagupan City', 'region_code' => '010000000',
            'province_code' => null, 'is_city' => true, 'city_class' => 'ICC',
        ]);

        $this->widen('cities', 'province_code', 'NULL');
        $this->migration()->up();

        $this->assertTrue($this->isNullable('cities', 'province_code'));
        $this->assertFalse($this->isNullable('cities', 'code'));
        $this->assertNull(City::query()->find('013301000')->province_code);
    }

    public function test_it_preserves_the_primary_key_and_indexes(): void
    {
        $before = $this->indexNames('cities');

        $this->widen('cities', 'code');
        $this->widen('cities', 'region_code');
        $this->migration()->up();

        $this->assertSame($before, $this->indexNames('cities'));
        $this->assertContains('PRIMARY', $before);
    }

    private function indexNames(string $table): array
    {
        $names = collect(DB::select(
            'SELECT DISTINCT INDEX_NAME AS n FROM INFORMATION_SCHEMA.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        ))->pluck('n')->sort()->values()->all();

        return $names;
    }

    public function test_it_refuses_to_truncate_oversized_data(): void
    {
        $this->widen('cities', 'city_class', 'NULL');

        DB::table('cities')->insert([
            'code' => '012801000', 'name' => 'Laoag City', 'region_code' => '010000000',
            'province_code' => '012800000', 'is_city' => 1,
            'city_class' => str_repeat('X', 30),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/30 characters long/');

        $this->migration()->up();
    }

    public function test_it_leaves_correctly_sized_columns_alone(): void
    {
        // A fresh install is already varchar(20); the migration should no-op.
        $this->assertSame(20, (int) $this->lengthOf('cities', 'code'));

        $this->migration()->up();

        $this->assertSame(20, (int) $this->lengthOf('cities', 'code'));
    }

    public function test_it_is_reversible(): void
    {
        $this->migration()->down();
        $this->assertSame(255, (int) $this->lengthOf('cities', 'code'));
        $this->assertTrue($this->isNullable('cities', 'province_code'));

        $this->migration()->up();
        $this->assertSame(20, (int) $this->lengthOf('cities', 'code'));
    }
}
