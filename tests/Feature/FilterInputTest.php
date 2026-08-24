<?php

namespace Schoolees\Psgc\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Schoolees\Psgc\Models\City;
use Schoolees\Psgc\Models\Region;
use Schoolees\Psgc\Tests\TestCase;

class FilterInputTest extends TestCase
{
    protected function seedCities(): void
    {
        City::query()->create([
            'code'          => '012801000',
            'name'          => 'Laoag City',
            'region_code'   => '010000000',
            'province_code' => '012800000',
            'is_city'       => true,
            'city_class'    => 'CC',
        ]);

        City::query()->create([
            'code'          => '012802000',
            'name'          => 'Bacarra',
            'region_code'   => '010000000',
            'province_code' => '012800000',
            'is_city'       => false,
            'city_class'    => 'MUN',
        ]);
    }

    protected function seedRegions(): void
    {
        Region::query()->create(['code' => '010000000', 'name' => 'Ilocos Region', 'short_name' => 'Region I']);
        Region::query()->create(['code' => '130000000', 'name' => 'National Capital Region', 'short_name' => 'NCR']);
    }

    /**
     * Regression: a boolean column was compared against the literal string "true",
     * which matched nothing on SQLite and matched the opposite value on MySQL.
     */
    #[DataProvider('booleanFilterProvider')]
    public function test_boolean_filters_match_the_intended_rows(string $value, string $expected): void
    {
        $this->seedCities();

        $response = $this->getJson("/psgc/cities?is_city={$value}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', $expected);
    }

    public static function booleanFilterProvider(): array
    {
        return [
            'true'  => ['true', 'Laoag City'],
            'false' => ['false', 'Bacarra'],
            '1'     => ['1', 'Laoag City'],
            '0'     => ['0', 'Bacarra'],
        ];
    }

    public function test_an_unparseable_boolean_filter_is_ignored_rather_than_inverted(): void
    {
        $this->seedCities();

        $this->getJson('/psgc/cities?is_city=maybe')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** Regression: two LIKE filters were OR'd, so adding a filter could widen the result set. */
    public function test_multiple_like_filters_narrow_the_result_set(): void
    {
        $this->seedCities();

        // Bacarra is a MUN, so pairing it with city_class=CC must match nothing.
        $this->getJson('/psgc/cities?name=Bacarra&city_class=CC')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('recordsTotal', 0);

        $this->getJson('/psgc/cities?name=Bacarra&city_class=MUN')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Bacarra');
    }

    public function test_exact_and_like_filters_combine_with_and(): void
    {
        $this->seedRegions();

        $this->getJson('/psgc/regions?name=Ilocos&code=130000000')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /** Regression: an array-valued parameter was cast to string and returned a 500. */
    public function test_array_valued_filters_do_not_error(): void
    {
        $this->seedRegions();

        $this->getJson('/psgc/regions?name[]=Ilocos&name[]=Capital')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/psgc/regions?code[]=010000000')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_array_valued_sort_and_limit_input_does_not_error(): void
    {
        $this->seedRegions();

        $this->getJson('/psgc/regions?order_by[]=name&sort_by[]=desc&limit[]=5')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('recordsPerPage', 10)
            ->assertJsonPath('data.0.name', 'Ilocos Region');
    }

    /** Regression: a blank parameter became `where code = ''`, matching no rows. */
    public function test_blank_filters_are_ignored(): void
    {
        $this->seedRegions();

        $this->getJson('/psgc/regions?code=')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/psgc/regions?name=&short_name=')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_unknown_parameters_are_ignored(): void
    {
        $this->seedRegions();

        $this->getJson('/psgc/regions?bogus=1&drop=table')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_filters_still_apply_when_valid(): void
    {
        $this->seedCities();

        $this->getJson('/psgc/cities?region_code=010000000&is_city=true')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', '012801000');
    }
}
