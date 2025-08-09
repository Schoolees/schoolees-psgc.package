<?php

namespace Schoolees\Psgc\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakePsgcModels extends Command
{
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
            $this->files->ensureDirectoryExists(dirname($path));

            if ($this->files->exists($path) && ! $this->option('force')) {
                $this->warn("⚠️  Exists: {$path} (use --force to overwrite)");
                continue;
            }

            $this->files->put($path, $content);
            $this->info("✅ Wrote: {$path}");
        }

        $this->info('🎯 PSGC models generated.');
        return self::SUCCESS;
    }

    /* ====================
       Model builders
       ==================== */

    private function regionModel(bool $soft): string
    {
        $imports = "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\nuse Illuminate\\Database\\Eloquent\\Model;\nuse Illuminate\\Database\\Eloquent\\Relations\\HasMany;";
        $traitUse = "use HasFactory;";
        if ($soft) { $imports .= "\nuse Illuminate\\Database\\Eloquent\\SoftDeletes;"; $traitUse = "use HasFactory, SoftDeletes;"; }

        return <<<PHP
<?php

namespace App\Models;

$imports

/**
 * @method static where(array \$param)
 */
class Region extends Model
{
    $traitUse

    protected \$table = config('psgc.tables.regions', 'regions');

    protected \$primaryKey = 'code';
    public \$incrementing = false;
    protected \$keyType = 'string';

    protected \$fillable = ['code','name','short_name'];

    public function getSearchable(): array
    {
        return [
            'query' => ['code'],
            'query_like' => ['name','short_name'],
        ];
    }

    public function provinces(): HasMany
    {
        return \$this->hasMany(Province::class, 'region_code', 'code');
    }
}
PHP;
    }

    private function provinceModel(bool $soft): string
    {
        $imports = "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\nuse Illuminate\\Database\\Eloquent\\Model;\nuse Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;\nuse Illuminate\\Database\\Eloquent\\Relations\\HasMany;";
        $traitUse = "use HasFactory;";
        if ($soft) { $imports .= "\nuse Illuminate\\Database\\Eloquent\\SoftDeletes;"; $traitUse = "use HasFactory, SoftDeletes;"; }

        return <<<PHP
<?php

namespace App\Models;

$imports

/**
 * @method static where(array \$param)
 */
class Province extends Model
{
    $traitUse

    protected \$table = config('psgc.tables.provinces', 'provinces');

    protected \$primaryKey = 'code';
    public \$incrementing = false;
    protected \$keyType = 'string';

    protected \$fillable = ['code','name','region_code'];

    public function getSearchable(): array
    {
        return [
            'query' => ['code','region_code'],
            'query_like' => ['name'],
        ];
    }

    public function region(): BelongsTo
    {
        return \$this->belongsTo(Region::class, 'region_code', 'code');
    }

    public function cities(): HasMany
    {
        return \$this->hasMany(City::class, 'province_code', 'code');
    }
}
PHP;
    }

    private function cityModel(bool $soft): string
    {
        $imports = "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\nuse Illuminate\\Database\\Eloquent\\Model;\nuse Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;\nuse Illuminate\\Database\\Eloquent\\Relations\\HasMany;";
        $traitUse = "use HasFactory;";
        if ($soft) { $imports .= "\nuse Illuminate\\Database\\Eloquent\\SoftDeletes;"; $traitUse = "use HasFactory, SoftDeletes;"; }

        return <<<PHP
<?php

namespace App\Models;

$imports

/**
 * @method static where(array \$param)
 */
class City extends Model
{
    $traitUse

    protected \$table = config('psgc.tables.cities', 'cities');

    protected \$primaryKey = 'code';
    public \$incrementing = false;
    protected \$keyType = 'string';

    protected \$fillable = ['code','name','region_code','province_code','is_city','city_class'];

    public function getSearchable(): array
    {
        return [
            'query' => ['code','region_code','province_code'],
            'query_like' => ['name'],
        ];
    }

    public function region(): BelongsTo
    {
        return \$this->belongsTo(Region::class, 'region_code', 'code');
    }

    public function province(): BelongsTo
    {
        return \$this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function barangays(): HasMany
    {
        return \$this->hasMany(Barangay::class, 'city_code', 'code');
    }
}
PHP;
    }

    private function barangayModel(bool $soft): string
    {
        $imports = "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\nuse Illuminate\\Database\\Eloquent\\Model;\nuse Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;";
        $traitUse = "use HasFactory;";
        if ($soft) { $imports .= "\nuse Illuminate\\Database\\Eloquent\\SoftDeletes;"; $traitUse = "use HasFactory, SoftDeletes;"; }

        return <<<PHP
<?php

namespace App\Models;

$imports

/**
 * @method static where(array \$param)
 */
class Barangay extends Model
{
    $traitUse

    protected \$table = config('psgc.tables.barangays', 'barangays');

    protected \$primaryKey = 'code';
    public \$incrementing = false;
    protected \$keyType = 'string';

    protected \$fillable = ['code','name','city_code'];

    public function getSearchable(): array
    {
        return [
            'query' => ['code','city_code'],
            'query_like' => ['name'],
        ];
    }

    public function city(): BelongsTo
    {
        return \$this->belongsTo(City::class, 'city_code', 'code');
    }
}
PHP;
    }
}
