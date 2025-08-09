<?php

namespace Schoolees\Psgc\Providers;

use Illuminate\Support\ServiceProvider;
use Schoolees\Psgc\Console\InstallPsgcCommand;
use Schoolees\Psgc\Console\Commands\MakePsgcModels;
use Schoolees\Psgc\Console\Commands\PublishPsgcRoutes;

// one-step publish+append

class PsgcServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/psgc.php', 'psgc');
    }

    public function boot(): void
    {
        // Register routes ONCE with configurable prefix/middleware
        if (! $this->app->routesAreCached()) {
            $this->app['router']->group([
                'as'         => 'psgc.',
                'prefix'     => config('psgc.api_prefix', 'psgc'),
                'middleware' => config('psgc.middleware', ['api']),
            ], function () {
                require __DIR__ . '/../../routes/api.php'; // <- unprefixed file
            });
        }

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Publishable assets
        $this->publishes([__DIR__ . '/../../config/psgc.php'                 => config_path('psgc.php')], 'psgc-config');
        $this->publishes([__DIR__ . '/../../resources/psgc'                  => base_path('resources/psgc')], 'psgc-resources');
        $this->publishes([__DIR__ . '/../../database/migrations'             => database_path('migrations')], 'psgc-migrations');
        $this->publishes([__DIR__ . '/../../database/seeders/PSGCSeeder.php' => database_path('seeders/PSGCSeeder.php')], 'psgc-seeders');
        $this->publishes([__DIR__ . '/../../routes/psgc.php'                 => base_path('routes/psgc.php')], 'psgc-routes');

        // CLI commands (no duplicates)
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallPsgcCommand::class,
                MakePsgcModels::class,
                PublishPsgcRoutes::class, // one-step: publishes routes + appends to routes/api.php
                \Schoolees\Psgc\Console\InstallPsgcCommand::class,
                \Schoolees\Psgc\Console\TestPublishCommand::class,
            ]);
        }
    }
}
