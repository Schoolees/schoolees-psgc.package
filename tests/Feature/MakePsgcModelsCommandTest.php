<?php

namespace Schoolees\Psgc\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Schoolees\Psgc\Tests\TestCase;

class MakePsgcModelsCommandTest extends TestCase
{
    protected Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem();
    }

    protected function tearDown(): void
    {
        foreach (['Region', 'Province', 'City', 'Barangay'] as $name) {
            $path = app_path("Models/{$name}.php");

            if ($this->files->exists($path)) {
                $this->files->delete($path);
            }
        }

        parent::tearDown();
    }

    public function test_generated_models_match_current_package_capabilities(): void
    {
        $this->artisan('make:psgc-models', ['--force' => true])->assertExitCode(0);

        $region = $this->files->get(app_path('Models/Region.php'));
        $city = $this->files->get(app_path('Models/City.php'));

        $this->assertStringContainsString("public function cities(): HasMany", $region);
        $this->assertStringContainsString("'query_like' => ['name','short_name']", $region);
        $this->assertStringContainsString("protected \$casts = [", $city);
        $this->assertStringContainsString("'is_city' => 'boolean'", $city);
        $this->assertStringContainsString("'query' => ['code','region_code','province_code','is_city']", $city);
        $this->assertStringContainsString("'query_like' => ['name','city_class']", $city);
    }
}
