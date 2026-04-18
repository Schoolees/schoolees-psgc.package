<?php

namespace Schoolees\Psgc\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

class PublishPsgcRoutes extends Command
{
    protected $signature = 'psgc:publish-routes {--force : Overwrite published routes/psgc.php if it exists}';
    protected $description = 'Publish routes/psgc.php and optionally append a PSGC route group to routes/web.php';

    public function __construct(protected Filesystem $files) { parent::__construct(); }

    public function handle(): int
    {
        // 1) Publish the package route file
        $code = Artisan::call('vendor:publish', [
            '--tag'   => 'psgc-routes',
            '--force' => (bool) $this->option('force'),
        ]);
        $this->output->write(Artisan::output());

        if (! config('psgc.append_include_on_publish', false)) {
            $this->warn('Skipping routes/api.php include append (psgc.append_include_on_publish=false).');
            $this->warn('If using published routes, set psgc.register_package_routes=false to avoid duplicate endpoints.');
            return $code === 0 ? self::SUCCESS : self::FAILURE;
        }

        // 2) Append include to routes/web.php so published routes keep the same /psgc/* URL shape.
        $web = base_path('routes/web.php');
        $include = <<<'PHP'
\Illuminate\Support\Facades\Route::middleware(config('psgc.middleware', ['api']))
    ->prefix(config('psgc.api_prefix', 'psgc'))
    ->group(base_path('routes/psgc.php'));
PHP;

        if (! $this->files->exists($web)) {
            $this->files->put($web, "<?php\n\n{$include}\n");
            $this->info('✅ Created routes/web.php and added PSGC route group.');
            return self::SUCCESS;
        }

        $content = $this->files->get($web);
        if (str_contains($content, $include)) {
            $this->info('✅ PSGC route group already present in routes/web.php');
            return self::SUCCESS;
        }

        $this->files->append($web, PHP_EOL . $include . PHP_EOL);
        $this->info('✅ Appended PSGC route group to routes/web.php');
        return $code === 0 ? self::SUCCESS : self::FAILURE;
    }
}
