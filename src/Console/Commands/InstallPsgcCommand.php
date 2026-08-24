<?php

namespace Schoolees\Psgc\Console\Commands;

use Illuminate\Console\Command;

class InstallPsgcCommand extends Command
{
    protected $signature = 'psgc:install
        {--force : Overwrite published package files}
        {--seed : Run PSGC seeder after migrating}';

    protected $description = 'Publish PSGC assets, migrate, and optionally seed';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        // Publish config and resources
        $this->call('vendor:publish', ['--tag' => 'psgc-config', '--force' => $force]);
        $this->call('vendor:publish', ['--tag' => 'psgc-resources', '--force' => $force]);

        // Publish seeder
        $this->call('vendor:publish', ['--tag' => 'psgc-seeders', '--force' => $force]);

        // Run migrations
        $this->call('migrate');

        // Optionally run seeder
        if ($this->option('seed')) {
            $this->call('db:seed', ['--class' => \Database\Seeders\PSGCSeeder::class]);
        }

        if (! $force) {
            $this->info('Existing published files were preserved. Use --force to overwrite them.');
        }

        $this->info('✅ PSGC installed successfully.');
        $this->info('📌 Endpoints available at: /' . config('psgc.api_prefix', 'psgc') . '/*');

        return self::SUCCESS;
    }
}
