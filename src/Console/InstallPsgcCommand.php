<?php

namespace Schoolees\Psgc\Console;

use Illuminate\Console\Command;

class InstallPsgcCommand extends Command
{
    protected $signature = 'psgc:install {--seed : Run PSGC seeder after migrating}';
    protected $description = 'Publish PSGC assets, migrate, and optionally seed';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'psgc-config', '--force' => true]);
        $this->call('vendor:publish', ['--tag' => 'psgc-resources', '--force' => true]);

        $this->call('migrate');

        if ($this->option('seed')) {
            $this->call('db:seed', ['--class' => 'Database\\Seeders\\PSGCSeeder']);
        }

        $this->info('PSGC installed. Endpoints: /api/' . config('psgc.route_prefix', 'psgc') . '/*');
        return self::SUCCESS;
    }
}
