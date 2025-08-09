<?php

namespace Schoolees\Psgc\Providers;

use Illuminate\Support\ServiceProvider;
use Schoolees\Psgc\Console\InstallPsgcCommand;
use Schoolees\Psgc\Console\Commands\MakePsgcModels;

class PsgcServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge package config (host app can override via config/psgc.php)
        $this->mergeConfigFrom(__DIR__ . '/../../config/psgc.php', 'psgc');
    }

    public function boot(): void
    {
        // Routes with name prefix
        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');

            // Add route group wrapper for prefix & naming
            $this->app['router']->group([
                'as' => 'psgc.',
                'prefix' => config('psgc.api_prefix', 'psgc'),
                'middleware' => config('psgc.middleware', ['api']),
            ], function ($router) {
                require __DIR__ . '/../../routes/api.php';
            });
        }

        $this->app['router']->group([
            'as' => 'psgc.',
            'prefix' => config('psgc.api_prefix', 'psgc'),
            'middleware' => config('psgc.middleware', ['api']),
        ], function ($router) {
            require __DIR__ . '/../../routes/api.php';
        });

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Publishable assets
        $this->publishes([
            __DIR__ . '/../../config/psgc.php' => config_path('psgc.php'),
        ], 'psgc-config');

        $this->publishes([
            __DIR__ . '/../../resources/psgc' => base_path('resources/psgc'),
        ], 'psgc-resources');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'psgc-migrations');

        $this->publishes([
            __DIR__ . '/../../database/seeders/PSGCSeeder.php' => database_path('seeders/PSGCSeeder.php'),
        ], 'psgc-seeders');

        $this->publishes([
            __DIR__ . '/../../config/psgc.php' => config_path('psgc.php'),
            __DIR__ . '/../../resources/psgc' => base_path('resources/psgc'),
            __DIR__ . '/../../database/seeders/PSGCSeeder.php' => database_path('seeders/PSGCSeeder.php'),
        ], 'psgc');

        // CLI commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallPsgcCommand::class,
                MakePsgcModels::class,
                \Schoolees\Psgc\Console\TestPublishCommand::class, // new test Commands
            ]);
        }
    }
}
