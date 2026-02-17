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
}

