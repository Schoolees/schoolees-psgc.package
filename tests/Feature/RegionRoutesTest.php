<?php

namespace Schoolees\Psgc\Tests\Feature;

use Schoolees\Psgc\Models\Region;
use Schoolees\Psgc\Tests\TestCase;

class RegionRoutesTest extends TestCase
{
    public function test_regions_endpoint_returns_paginated_payload(): void
    {
        Region::query()->create([
            'code' => '130000000',
            'name' => 'National Capital Region',
            'short_name' => 'NCR',
        ]);

        $response = $this->getJson('/psgc/regions?draw=7&limit=10');

        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('draw', 7)
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('recordsPerPage', 10)
            ->assertJsonPath('data.0.code', '130000000')
            ->assertJsonPath('data.0.short_name', 'NCR');
    }

    public function test_regions_endpoint_applies_filters_and_limit_guardrails(): void
    {
        Region::query()->create([
            'code' => '010000000',
            'name' => 'Ilocos Region',
            'short_name' => 'Region I',
        ]);

        Region::query()->create([
            'code' => '130000000',
            'name' => 'National Capital Region',
            'short_name' => 'NCR',
        ]);

        $response = $this->getJson('/psgc/regions?name=Capital&limit=500');

        $response->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('recordsPerPage', 100)
            ->assertJsonPath('data.0.code', '130000000')
            ->assertJsonPath('filters.name', 'Capital')
            ->assertJsonPath('filters.limit', '500');
    }

    public function test_invalid_sort_inputs_fall_back_to_configured_defaults(): void
    {
        Region::query()->create([
            'code' => '130000000',
            'name' => 'National Capital Region',
            'short_name' => 'NCR',
        ]);

        Region::query()->create([
            'code' => '010000000',
            'name' => 'Ilocos Region',
            'short_name' => 'Region I',
        ]);

        $response = $this->getJson('/psgc/regions?order_by=invalid_column&sort_by=sideways');

        $response->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('data.0.name', 'Ilocos Region')
            ->assertJsonPath('data.1.name', 'National Capital Region');
    }

    public function test_invalid_configured_default_sort_column_falls_back_to_allowed_column(): void
    {
        config()->set('psgc.order_by', 'invalid_default');

        Region::query()->create([
            'code' => '130000000',
            'name' => 'National Capital Region',
            'short_name' => 'NCR',
        ]);

        Region::query()->create([
            'code' => '010000000',
            'name' => 'Ilocos Region',
            'short_name' => 'Region I',
        ]);

        $response = $this->getJson('/psgc/regions');

        $response->assertOk()
            ->assertJsonPath('data.0.code', '010000000')
            ->assertJsonPath('data.1.code', '130000000');
    }
}
