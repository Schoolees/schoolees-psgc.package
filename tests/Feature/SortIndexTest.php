<?php

namespace Schoolees\Psgc\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Schoolees\Psgc\Tests\TestCase;

class SortIndexTest extends TestCase
{
    /**
     * Regression: `name` is the default sort column, but on provinces, cities and
     * barangays it only existed as the trailing column of a composite index, which
     * `ORDER BY name` cannot use. Every unfiltered listing full-scanned and
     * filesorted the table — ~42k rows for barangays.
     */
    /**
     * Every column a caller may sort by must have an index behind it, or the
     * listing filesorts the whole table. Mirrors the allowedOrderBy lists in
     * src/Services/*Service.php.
     *
     * @return array<string, array<int, string>>
     */
    public static function sortableColumns(): array
    {
        return [
            'regions'   => ['code', 'name', 'short_name'],
            'provinces' => ['code', 'name', 'region_code'],
            'cities'    => ['code', 'name', 'region_code', 'province_code', 'is_city', 'city_class'],
            'barangays' => ['code', 'name', 'city_code'],
        ];
    }

    public function test_every_sortable_column_is_backed_by_an_index(): void
    {
        if ($this->onMysql()) {
            $this->markTestSkipped('EXPLAIN QUERY PLAN is SQLite-specific.');
        }

        foreach (self::sortableColumns() as $table => $columns) {
            foreach ($columns as $column) {
                $plan = collect(DB::select("EXPLAIN QUERY PLAN SELECT * FROM {$table} ORDER BY {$column} ASC LIMIT 10"))
                    ->pluck('detail')
                    ->implode(' | ');

                $this->assertStringNotContainsString(
                    'TEMP B-TREE',
                    $plan,
                    "ORDER BY {$column} on `{$table}` falls back to a filesort. Plan: {$plan}"
                );
            }
        }
    }

    public function test_default_sort_column_is_backed_by_a_usable_index(): void
    {
        if ($this->onMysql()) {
            $this->markTestSkipped('EXPLAIN QUERY PLAN is SQLite-specific; MySQL is covered by the column-width test.');
        }

        $tables = [
            (string) config('psgc.tables.regions', 'regions'),
            (string) config('psgc.tables.provinces', 'provinces'),
            (string) config('psgc.tables.cities', 'cities'),
            (string) config('psgc.tables.barangays', 'barangays'),
        ];

        foreach ($tables as $table) {
            $plan = collect(DB::select("EXPLAIN QUERY PLAN SELECT * FROM {$table} ORDER BY name ASC LIMIT 10"))
                ->pluck('detail')
                ->implode(' | ');

            $this->assertStringNotContainsString(
                'TEMP B-TREE',
                $plan,
                "ORDER BY name on `{$table}` falls back to a filesort. Plan: {$plan}"
            );
        }
    }

    public function test_default_listing_is_ordered_by_name(): void
    {
        \Schoolees\Psgc\Models\Barangay::query()->create(['code' => '2', 'name' => 'Zulu', 'city_code' => 'c']);
        \Schoolees\Psgc\Models\Barangay::query()->create(['code' => '1', 'name' => 'Alpha', 'city_code' => 'c']);

        $this->getJson('/psgc/barangays')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Alpha')
            ->assertJsonPath('data.1.name', 'Zulu');
    }
}
