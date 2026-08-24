<?php

namespace Schoolees\Psgc\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Schoolees\Psgc\Providers\PsgcServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PsgcServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Defaults to in-memory SQLite. CI also runs the suite against MySQL,
        // which is the only place VARCHAR lengths and native booleans are real.
        if (env('DB_CONNECTION') === 'mysql') {
            $app['config']->set('database.connections.testing', [
                'driver'    => 'mysql',
                'host'      => env('DB_HOST', '127.0.0.1'),
                'port'      => env('DB_PORT', '3306'),
                'database'  => env('DB_DATABASE', 'psgc_test'),
                'username'  => env('DB_USERNAME', 'root'),
                'password'  => env('DB_PASSWORD', ''),
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
            ]);
        } else {
            $app['config']->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }

        $app['config']->set('database.default', 'testing');
        $app['config']->set('psgc.middleware', []);
    }

    protected function onMysql(): bool
    {
        return $this->app['db']->connection()->getDriverName() === 'mysql';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->artisan('migrate')->run();
    }
}

