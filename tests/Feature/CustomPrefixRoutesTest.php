<?php

namespace Schoolees\Psgc\Tests\Feature;

use Schoolees\Psgc\Models\Region;
use Schoolees\Psgc\Tests\TestCase;

class CustomPrefixRoutesTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('psgc.api_prefix', 'geo');
    }

    public function test_routes_honor_the_configured_prefix(): void
    {
        Region::query()->create([
            'code' => '130000000',
            'name' => 'National Capital Region',
            'short_name' => 'NCR',
        ]);

        $this->getJson('/geo/regions')
            ->assertOk()
            ->assertJsonPath('data.0.code', '130000000');
    }
}
