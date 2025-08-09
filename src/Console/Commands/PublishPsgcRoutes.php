<?php

namespace Schoolees\Psgc\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

class PublishPsgcRoutes extends Command
{
    protected $signature = 'psgc:publish-routes {--force : Overwrite published routes/psgc.php if it exists}';
    protected $description = 'Publish routes/psgc.php and append require to routes/api.php if missing';

    public function __construct(protected Filesystem $files) { parent::__construct(); }

    public function handle(): int
    {
        // 1) Publish the package route file
        $code = Artisan::call('vendor:publish', [
            '--tag'   => 'psgc-routes',
            '--force' => (bool) $this->option('force'),
        ]);
        $this->output->write(Artisan::output());

        // 2) Append include to routes/api.php
        $api = base_path('routes/api.php');
        $include = "require base_path('routes/psgc.php');";

        if (! $this->files->exists($api)) {
            // Create a minimal api.php if none exists
            $this->files->put($api, "<?php\n\n{$include}\n");
            $this->info('✅ Created routes/api.php and added PSGC include.');
            return self::SUCCESS;
        }

        $content = $this->files->get($api);
        if (str_contains($content, $include)) {
            $this->info('✅ PSGC include already present in routes/api.php');
            return self::SUCCESS;
        }

        $this->files->append($api, PHP_EOL . $include . PHP_EOL);
        $this->info('✅ Appended PSGC include to routes/api.php');
        return $code === 0 ? self::SUCCESS : self::FAILURE;
    }
}
