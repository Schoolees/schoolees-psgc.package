<?php

namespace Schoolees\Psgc\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Schoolees\Psgc\Tests\TestCase;

/**
 * SQLite ignores VARCHAR lengths entirely, so these only mean anything on MySQL.
 * They are the check behind the 2.0 index-width fix.
 */
class ColumnWidthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->onMysql()) {
            $this->markTestSkipped('VARCHAR lengths are not enforced by SQLite.');
        }
    }

    public function test_code_columns_are_bounded(): void
    {
        $expected = [
            'regions'   => ['code'],
            'provinces' => ['code', 'region_code'],
            'cities'    => ['code', 'region_code', 'province_code', 'city_class'],
            'barangays' => ['code', 'city_code'],
        ];

        foreach ($expected as $table => $columns) {
            foreach ($columns as $column) {
                $length = DB::selectOne(
                    'SELECT CHARACTER_MAXIMUM_LENGTH AS len
                       FROM INFORMATION_SCHEMA.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                    [$table, $column]
                )?->len;

                $this->assertSame(
                    20,
                    (int) $length,
                    "{$table}.{$column} should be varchar(20), got varchar({$length})."
                );
            }
        }
    }

    /** InnoDB caps an index key at 3072 bytes; utf8mb4 is 4 bytes per character. */
    public function test_composite_indexes_stay_within_the_innodb_key_limit(): void
    {
        $rows = DB::select(
            "SELECT s.TABLE_NAME AS tbl, s.INDEX_NAME AS idx,
                    SUM(c.CHARACTER_MAXIMUM_LENGTH * 4) AS bytes
               FROM INFORMATION_SCHEMA.STATISTICS s
               JOIN INFORMATION_SCHEMA.COLUMNS c
                 ON c.TABLE_SCHEMA = s.TABLE_SCHEMA
                AND c.TABLE_NAME = s.TABLE_NAME
                AND c.COLUMN_NAME = s.COLUMN_NAME
              WHERE s.TABLE_SCHEMA = DATABASE()
                AND s.TABLE_NAME IN ('regions','provinces','cities','barangays')
                AND c.DATA_TYPE = 'varchar'
              GROUP BY s.TABLE_NAME, s.INDEX_NAME"
        );

        $this->assertNotEmpty($rows);

        foreach ($rows as $row) {
            $this->assertLessThan(
                3072,
                (int) $row->bytes,
                "Index {$row->tbl}.{$row->idx} is {$row->bytes} bytes, at or over InnoDB's 3072-byte key limit."
            );
        }
    }

    public function test_boolean_filters_work_against_a_native_boolean_column(): void
    {
        \Schoolees\Psgc\Models\City::query()->create([
            'code' => '012801000', 'name' => 'Laoag City', 'region_code' => '010000000',
            'province_code' => '012800000', 'is_city' => true, 'city_class' => 'CC',
        ]);
        \Schoolees\Psgc\Models\City::query()->create([
            'code' => '012802000', 'name' => 'Bacarra', 'region_code' => '010000000',
            'province_code' => '012800000', 'is_city' => false, 'city_class' => 'MUN',
        ]);

        // On MySQL the pre-2.0 bug matched the *opposite* value rather than nothing.
        $this->getJson('/psgc/cities?is_city=true')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laoag City');

        $this->getJson('/psgc/cities?is_city=false')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Bacarra');
    }
}
