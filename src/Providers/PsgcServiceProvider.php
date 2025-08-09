<?php

namespace Schoolees\Psgc\Providers;

use Illuminate\Support\ServiceProvider;
use Schoolees\Psgc\Console\InstallPsgcCommand;
use Schoolees\Psgc\Console\Commands\MakePsgcModels;
use Schoolees\Psgc\Console\Commands\PublishPsgcRoutes;

class PsgcServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Allow host apps to override config/psgc.php
        $this->mergeConfigFrom(__DIR__ . '/../../config/psgc.php', 'psgc');
    }

    public function boot(): void
    {
        /**
         * Routes
         * - Your package routes/api.php must be UNPREFIXED
         * - We wrap it here with the configurable /psgc prefix
         */
        if (! $this->app->routesAreCached()) {
            $this->app['router']->group([
                'as'         => 'psgc.',
                'prefix'     => config('psgc.api_prefix', 'psgc'),
                'middleware' => config('psgc.middleware', ['api']),
            ], function () {
                require __DIR__ . '/../../routes/api.php';
            });
        }

        /**
         * Migrations
         */
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        /**
         * Publishables
         */
        // Individual tags
        $this->publishes([__DIR__ . '/../../config/psgc.php'                 => config_path('psgc.php')], 'psgc-config');
        $this->publishes([__DIR__ . '/../../resources/psgc'                  => base_path('resources/psgc')], 'psgc-resources');
        $this->publishes([__DIR__ . '/../../database/migrations'             => database_path('migrations')], 'psgc-migrations');
        $this->publishes([__DIR__ . '/../../database/seeders/PSGCSeeder.php' => database_path('seeders/PSGCSeeder.php')], 'psgc-seeders');
        $this->publishes([__DIR__ . '/../../routes/psgc.php'                 => base_path('routes/psgc.php')], 'psgc-routes');

        // Publish HTTP Resources (package -> app), if you want to customize them in the host app
        $this->publishes([__DIR__ . '/../Http/Resources' => app_path('Http/Resources')], 'psgc-resources-classes');

        // Convenience umbrella tag
        $this->publishes([
            __DIR__ . '/../../config/psgc.php'                 => config_path('psgc.php'),
            __DIR__ . '/../../resources/psgc'                  => base_path('resources/psgc'),
            __DIR__ . '/../../database/seeders/PSGCSeeder.php' => database_path('seeders/PSGCSeeder.php'),
            __DIR__ . '/../../routes/psgc.php'                 => base_path('routes/psgc.php'),
        ], 'psgc');

        /**
         * Console commands
         */
        if ($this->app->runningInConsole()) {
            $commands = [
                InstallPsgcCommand::class,
                MakePsgcModels::class,
                PublishPsgcRoutes::class, // one-step: publish routes file + append include to routes/api.php
            ];

            // Optionally register if present in your package
            if (class_exists(\Schoolees\Psgc\Console\TestPublishCommand::class)) {
                $commands[] = \Schoolees\Psgc\Console\TestPublishCommand::class;
            }

            $this->commands($commands);
        }
    }
}
