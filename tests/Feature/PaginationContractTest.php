<?php

namespace Schoolees\Psgc\Tests\Feature;

use Schoolees\Psgc\Models\Region;
use Schoolees\Psgc\Tests\TestCase;

class PaginationContractTest extends TestCase
{
    protected function seedRegions(): void
    {
        foreach (['A', 'B', 'C', 'D'] as $letter) {
            Region::query()->create([
                'code'       => "{$letter}0000000",
                'name'       => "Region {$letter}",
                'short_name' => $letter,
            ]);
        }
    }

    /** Regression: `?page=` was ignored entirely, so page 2 returned page 1. */
    public function test_page_query_parameter_selects_the_requested_page(): void
    {
        $this->seedRegions();

        $this->getJson('/psgc/regions?limit=2')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Region A')
            ->assertJsonPath('data.1.name', 'Region B');

        $this->getJson('/psgc/regions?limit=2&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Region C')
            ->assertJsonPath('data.1.name', 'Region D');
    }

    /** Regression: the paginator advertised a `next` link that returned page 1 again. */
    public function test_the_advertised_next_link_returns_the_following_page(): void
    {
        config()->set('psgc.response_format', 'pagination');
        $this->seedRegions();

        $first = $this->getJson('/psgc/regions?limit=2');
        $first->assertOk()->assertJsonPath('data.0.name', 'Region A');

        $next = $first->json('links.next');
        $this->assertNotNull($next, 'Expected a next link on page 1 of 2.');

        $this->getJson($next)
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Region C')
            ->assertJsonPath('data.1.name', 'Region D');
    }

    /** Regression: `offset` was rounded down to a page boundary, so offset=1 skipped nothing. */
    public function test_offset_skips_exactly_the_requested_number_of_rows(): void
    {
        $this->seedRegions();

        $response = $this->getJson('/psgc/regions?limit=10&offset=1');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Region B')
            ->assertJsonPath('recordsTotal', 4);
    }

    public function test_offset_is_reported_honestly_in_pagination_meta(): void
    {
        config()->set('psgc.response_format', 'pagination');
        $this->seedRegions();

        $this->getJson('/psgc/regions?limit=10&offset=1')
            ->assertOk()
            ->assertJsonPath('meta.from', 2)
            ->assertJsonPath('meta.to', 4)
            ->assertJsonPath('meta.total', 4);
    }

    public function test_offset_beyond_the_result_set_returns_no_rows(): void
    {
        $this->seedRegions();

        $this->getJson('/psgc/regions?limit=10&offset=99')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('recordsTotal', 4);
    }

    public function test_page_takes_precedence_over_a_stale_offset(): void
    {
        $this->seedRegions();

        $this->getJson('/psgc/regions?limit=2&offset=0&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Region C');
    }

    public function test_non_numeric_pagination_input_falls_back_to_defaults(): void
    {
        $this->seedRegions();

        $this->getJson('/psgc/regions?limit=abc&offset=xyz&page=nope')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('recordsPerPage', 10)
            ->assertJsonPath('data.0.name', 'Region A');
    }
}
