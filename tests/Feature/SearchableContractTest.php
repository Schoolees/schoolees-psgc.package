<?php

namespace Schoolees\Psgc\Tests\Feature;

use Schoolees\Psgc\Models\City;
use Schoolees\Psgc\Support\Utility;
use Schoolees\Psgc\Tests\TestCase;

class SearchableContractTest extends TestCase
{
    protected function seedCities(): void
    {
        City::query()->create([
            'code' => '012801000', 'name' => 'Laoag City', 'region_code' => '010000000',
            'province_code' => '012800000', 'is_city' => true, 'city_class' => 'CC',
        ]);
        City::query()->create([
            'code' => '013301000', 'name' => 'Dagupan City', 'region_code' => '010000000',
            'province_code' => null, 'is_city' => true, 'city_class' => 'ICC',
        ]);
    }

    /** 2.0: city_class moved from a LIKE filter to an exact one. */
    public function test_city_class_matches_exactly_rather_than_as_a_substring(): void
    {
        $this->seedCities();

        // As a LIKE filter this also matched ICC.
        $this->getJson('/psgc/cities?city_class=CC')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.city_class', 'CC');

        $this->getJson('/psgc/cities?city_class=ICC')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.city_class', 'ICC');
    }

    /**
     * 2.0: timestamps are no longer sortable. The seeder stamps every row with
     * the same value, so ordering by them was meaningless and unindexed.
     */
    public function test_timestamps_are_no_longer_sortable_and_fall_back_to_the_default(): void
    {
        $this->seedCities();

        // The column falls back to the configured default (name); the direction
        // is validated separately and is still honoured.
        $this->getJson('/psgc/cities?order_by=created_at')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Dagupan City')
            ->assertJsonPath('data.1.name', 'Laoag City');

        $this->getJson('/psgc/cities?order_by=created_at&sort_by=desc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Laoag City')
            ->assertJsonPath('data.1.name', 'Dagupan City');
    }

    /** 2.0: the \App\Libraries\UtilityLibrary fallback is gone. */
    public function test_the_legacy_host_app_hook_is_removed(): void
    {
        $this->assertFalse(
            method_exists(Utility::class, 'legacyHostFormatter'),
            'The deprecated host-app formatter hook should be gone in 2.0.'
        );

        $source = file_get_contents((new \ReflectionClass(Utility::class))->getFileName());
        $this->assertStringNotContainsString('UtilityLibrary', $source);
    }
}
