<?php

namespace Schoolees\Psgc\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Schoolees\Psgc\Models\Region;
use Schoolees\Psgc\Support\PsgcCache;
use Schoolees\Psgc\Tests\TestCase;

class CachingTest extends TestCase
{
    protected function seedRegions(): void
    {
        Region::query()->create(['code' => '010000000', 'name' => 'Ilocos Region', 'short_name' => 'Region I']);
        Region::query()->create(['code' => '130000000', 'name' => 'National Capital Region', 'short_name' => 'NCR']);
    }

    protected function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_caching_is_off_by_default(): void
    {
        $this->assertFalse(PsgcCache::enabled());

        $this->seedRegions();

        $first = $this->countQueries(fn () => $this->getJson('/psgc/regions')->assertOk());
        $second = $this->countQueries(fn () => $this->getJson('/psgc/regions')->assertOk());

        $this->assertSame($first, $second, 'Without caching both requests should hit the database equally.');
        $this->assertGreaterThan(0, $second);
    }

    public function test_a_repeated_listing_is_served_from_cache(): void
    {
        config()->set('psgc.cache.enabled', true);
        $this->seedRegions();

        $this->getJson('/psgc/regions')->assertOk();
        $cached = $this->countQueries(fn () => $this->getJson('/psgc/regions')->assertOk());

        $this->assertSame(0, $cached, 'The second identical listing should not query the database.');
    }

    public function test_a_different_query_is_cached_separately(): void
    {
        config()->set('psgc.cache.enabled', true);
        $this->seedRegions();

        $this->getJson('/psgc/regions')->assertOk();

        $this->getJson('/psgc/regions?name=Ilocos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ilocos Region');
    }

    public function test_a_repeated_show_is_served_from_cache(): void
    {
        config()->set('psgc.cache.enabled', true);
        $this->seedRegions();

        $this->getJson('/psgc/regions/010000000')->assertOk();
        $cached = $this->countQueries(fn () => $this->getJson('/psgc/regions/010000000')->assertOk());

        $this->assertSame(0, $cached);
    }

    public function test_flushing_invalidates_cached_results(): void
    {
        config()->set('psgc.cache.enabled', true);
        $this->seedRegions();

        $this->getJson('/psgc/regions')->assertOk()->assertJsonPath('recordsTotal', 2);

        Region::query()->create(['code' => '020000000', 'name' => 'Cagayan Valley', 'short_name' => 'Region II']);

        // Still the stale count, which is the point of caching.
        $this->getJson('/psgc/regions')->assertOk()->assertJsonPath('recordsTotal', 2);

        PsgcCache::flush();

        $this->getJson('/psgc/regions')->assertOk()->assertJsonPath('recordsTotal', 3);
    }

    public function test_the_cache_clear_command_flushes(): void
    {
        config()->set('psgc.cache.enabled', true);
        $this->seedRegions();

        $this->getJson('/psgc/regions')->assertOk()->assertJsonPath('recordsTotal', 2);
        Region::query()->create(['code' => '020000000', 'name' => 'Cagayan Valley', 'short_name' => 'Region II']);

        $this->artisan('psgc:cache-clear')->assertExitCode(0);

        $this->getJson('/psgc/regions')->assertOk()->assertJsonPath('recordsTotal', 3);
    }

    public function test_the_cache_clear_command_reports_when_caching_is_disabled(): void
    {
        $this->artisan('psgc:cache-clear')
            ->expectsOutputToContain('caching is disabled')
            ->assertExitCode(0);
    }
}
