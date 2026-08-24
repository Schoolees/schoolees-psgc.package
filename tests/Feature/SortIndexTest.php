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
    public function test_default_sort_column_is_backed_by_a_usable_index(): void
    {
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
