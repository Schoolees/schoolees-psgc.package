<?php

namespace Schoolees\Psgc\Console;

use Illuminate\Console\Command;

class TestPublishCommand extends Command
{
    protected $signature = 'psgc:test-publish';
    protected $description = 'Test if PSGC package publishable assets are detected';

    public function handle(): int
    {
        $paths = [
            config_path('psgc.php'),
            base_path('resources/psgc'),
            database_path('seeders/PSGCSeeder.php'),
        ];

        foreach ($paths as $path) {
            $this->line("Checking: {$path}");
            $this->info(file_exists($path) ? "✅ Found" : "❌ Missing");
        }

        $this->info('Test complete.');
        return self::SUCCESS;
    }
}
