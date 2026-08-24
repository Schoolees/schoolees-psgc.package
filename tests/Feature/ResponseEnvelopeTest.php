<?php

namespace Schoolees\Psgc\Tests\Feature;

use Illuminate\Support\Facades\Log;
use Schoolees\Psgc\Models\Region;
use Schoolees\Psgc\Services\RegionService;
use Schoolees\Psgc\Tests\TestCase;

class ResponseEnvelopeTest extends TestCase
{
    protected function seedRegions(): void
    {
        Region::query()->create(['code' => '010000000', 'name' => 'Ilocos Region', 'short_name' => 'Region I']);
        Region::query()->create(['code' => '130000000', 'name' => 'National Capital Region', 'short_name' => 'NCR']);
    }

    /** The host-app hook is config-driven rather than a hardcoded \App\ class reference. */
    public function test_a_configured_closure_formatter_replaces_the_envelope(): void
    {
        $this->seedRegions();

        config()->set('psgc.response_formatter', fn ($collection) => [
            'shape' => 'custom',
            'count' => $collection->total(),
        ]);

        $this->getJson('/psgc/regions')
            ->assertOk()
            ->assertExactJson(['shape' => 'custom', 'count' => 2]);
    }

    public function test_a_configured_class_formatter_replaces_the_envelope(): void
    {
        $this->seedRegions();

        config()->set('psgc.response_formatter', TestFormatter::class);

        $this->getJson('/psgc/regions')
            ->assertOk()
            ->assertJsonPath('formatted_by', TestFormatter::class);
    }

    public function test_a_configured_formatter_also_applies_in_pagination_mode(): void
    {
        config()->set('psgc.response_format', 'pagination');
        config()->set('psgc.response_formatter', fn ($c) => ['shape' => 'custom']);
        $this->seedRegions();

        // Previously the pagination branch returned before the host hook was consulted.
        $this->getJson('/psgc/regions')
            ->assertOk()
            ->assertExactJson(['shape' => 'custom']);
    }

    public function test_an_uncallable_formatter_is_reported_rather_than_ignored(): void
    {
        $this->seedRegions();

        config()->set('psgc.response_formatter', 'not-a-callable');
        config()->set('app.debug', true);

        $this->getJson('/psgc/regions')
            ->assertStatus(500)
            ->assertJsonPath('code', 500);
    }

    public function test_filters_echo_can_return_only_the_applied_filters(): void
    {
        config()->set('psgc.filters_echo', 'applied');
        $this->seedRegions();

        $this->getJson('/psgc/regions?name=Ilocos&limit=5&bogus=1&xss=<script>')
            ->assertOk()
            ->assertJsonPath('filters', ['name' => 'Ilocos']);
    }

    public function test_filters_echo_can_be_disabled(): void
    {
        config()->set('psgc.filters_echo', 'none');
        $this->seedRegions();

        $this->getJson('/psgc/regions?name=Ilocos')
            ->assertOk()
            ->assertJsonPath('filters', []);
    }

    public function test_filters_echo_defaults_to_the_whole_request(): void
    {
        $this->seedRegions();

        $this->getJson('/psgc/regions?name=Ilocos&bogus=1')
            ->assertOk()
            ->assertJsonPath('filters.name', 'Ilocos')
            ->assertJsonPath('filters.bogus', '1');
    }

    /** Regression: a 5xx was converted to JSON and left no trace in the log. */
    public function test_server_errors_are_logged(): void
    {
        Log::spy();

        $this->mock(RegionService::class, function ($mock) {
            $mock->shouldReceive('getRegions')->andThrow(new \RuntimeException('kaboom'));
        });

        $this->getJson('/psgc/regions')
            ->assertStatus(500)
            ->assertJsonPath('error', 'Server Error');

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($message) => str_contains($message, 'kaboom'))
            ->once();
    }

    public function test_exception_logging_can_be_disabled(): void
    {
        Log::spy();
        config()->set('psgc.log_exceptions', false);

        $this->mock(RegionService::class, function ($mock) {
            $mock->shouldReceive('getRegions')->andThrow(new \RuntimeException('kaboom'));
        });

        $this->getJson('/psgc/regions')->assertStatus(500);

        Log::shouldNotHaveReceived('error');
    }

    public function test_client_errors_are_not_logged_as_server_failures(): void
    {
        Log::spy();

        $this->mock(RegionService::class, function ($mock) {
            $mock->shouldReceive('getRegions')
                ->andThrow(new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('nope'));
        });

        $this->getJson('/psgc/regions')->assertStatus(404);

        Log::shouldNotHaveReceived('error');
    }
}

class TestFormatter
{
    public function dataTableResponse($collection): array
    {
        return ['formatted_by' => self::class, 'total' => $collection->total()];
    }
}
