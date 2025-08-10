<?php

namespace Schoolees\Psgc\Console;

use Illuminate\Console\Command;

class InstallPsgcCommand extends Command
{
    protected $signature = 'psgc:install {--seed : Run PSGC seeder after migrating}';
    protected $description = 'Publish PSGC assets, migrate, and optionally seed';

    public function handle(): int
    {
        // Publish config and resources
        $this->call('vendor:publish', ['--tag' => 'psgc-config', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'psgc-resources', '--force' => true]);

        // Publish seeder
        $this->call('vendor:publish', ['--tag' => 'psgc-seeders', '--force' => true]);

        // Run migrations
        $this->call('migrate');

        // Optionally run seeder
        if ($this->option('seed')) {
            $this->call('db:seed', ['--class' => \Database\Seeders\PSGCSeeder::class]);
        }

        $this->info('✅ PSGC installed successfully.');
        $this->info('📌 Endpoints available at: /' . config('psgc.api_prefix', 'psgc') . '/*');

        return self::SUCCESS;
    }
}
