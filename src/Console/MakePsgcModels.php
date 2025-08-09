<?php

namespace Schoolees\Psgc\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakePsgcModels extends Command
{
    // 👇 make sure this is not empty and unique
    protected $signature = 'make:psgc-models
        {--force : Overwrite existing files if present}
        {--softdeletes : Include SoftDeletes trait in generated models (off by default)}';

    protected $description = 'Generate Region, Province, City, and Barangay models (PSGC fields & relationships)';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $withSoftDeletes = (bool) $this->option('softdeletes');

        $models = [
            'Region'   => $this->regionModel($withSoftDeletes),
            'Province' => $this->provinceModel($withSoftDeletes),
            'City'     => $this->cityModel($withSoftDeletes),
            'Barangay' => $this->barangayModel($withSoftDeletes),
        ];

        foreach ($models as $name => $content) {
            $path = app_path("Models/{$name}.php");
            if ($this->files->exists($path) && !$this->option('force')) {
                $this->warn("⚠️ Exists: {$path} (use --force to overwrite)");
                continue;
            }
            $this->files->put($path, $content);
            $this->info("✅ Wrote: {$path}");
        }

        $this->info('🎯 PSGC models generated.');
        return self::SUCCESS;
    }

    // … keep your regionModel/provinceModel/cityModel/barangayModel() builders here …
}
