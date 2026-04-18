<?php

namespace Schoolees\Psgc\Tests\Feature;

use Schoolees\Psgc\Tests\TestCase;

class DisabledRouteRegistrationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('psgc.register_package_routes', false);
    }

    public function test_package_routes_can_be_disabled_via_config(): void
    {
        $this->getJson('/psgc/regions')->assertNotFound();
    }
}
