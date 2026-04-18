<?php

namespace Schoolees\Psgc\Tests\Feature;

use Schoolees\Psgc\Models\Region;
use Schoolees\Psgc\Tests\TestCase;

class PaginationResponseTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('psgc.response_format', 'pagination');
    }

    public function test_regions_endpoint_can_return_generic_pagination_payload(): void
    {
        Region::query()->create([
            'code' => '130000000',
            'name' => 'National Capital Region',
            'short_name' => 'NCR',
        ]);

        $response = $this->getJson('/psgc/regions?limit=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta',
                'links',
                'filters',
            ])
            ->assertJsonMissingPath('draw')
            ->assertJsonPath('data.0.code', '130000000')
            ->assertJsonPath('meta.per_page', 10);
    }
}
