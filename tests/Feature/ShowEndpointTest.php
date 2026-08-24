<?php

namespace Schoolees\Psgc\Tests\Feature;

use Schoolees\Psgc\Models\Barangay;
use Schoolees\Psgc\Models\City;
use Schoolees\Psgc\Models\Province;
use Schoolees\Psgc\Models\Region;
use Schoolees\Psgc\Tests\TestCase;

class ShowEndpointTest extends TestCase
{
    protected function seedAll(): void
    {
        Region::query()->create(['code' => '010000000', 'name' => 'Ilocos Region', 'short_name' => 'Region I']);
        Province::query()->create(['code' => '012800000', 'name' => 'Ilocos Norte', 'region_code' => '010000000']);
        City::query()->create([
            'code' => '012801000', 'name' => 'Laoag City', 'region_code' => '010000000',
            'province_code' => '012800000', 'is_city' => true, 'city_class' => 'CC',
        ]);
        Barangay::query()->create(['code' => '012801001', 'name' => 'Barangay 1', 'city_code' => '012801000']);
    }

    public static function endpointProvider(): array
    {
        return [
            'region'   => ['regions', '010000000', 'Ilocos Region'],
            'province' => ['provinces', '012800000', 'Ilocos Norte'],
            'city'     => ['cities', '012801000', 'Laoag City'],
            'barangay' => ['barangays', '012801001', 'Barangay 1'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpointProvider')]
    public function test_a_record_can_be_fetched_by_code(string $endpoint, string $code, string $name): void
    {
        $this->seedAll();

        $this->getJson("/psgc/{$endpoint}/{$code}")
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.code', $code)
            ->assertJsonPath('data.name', $name);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endpointProvider')]
    public function test_an_unknown_code_returns_404(string $endpoint, string $code, string $name): void
    {
        $this->seedAll();

        $this->getJson("/psgc/{$endpoint}/999999999")
            ->assertStatus(404)
            ->assertJsonPath('code', 404);
    }

    public function test_show_respects_the_pagination_envelope(): void
    {
        config()->set('psgc.response_format', 'pagination');
        $this->seedAll();

        $this->getJson('/psgc/regions/010000000')
            ->assertOk()
            ->assertJsonPath('data.code', '010000000')
            ->assertJsonMissingPath('code');
    }

    public function test_the_index_route_is_not_shadowed_by_the_show_route(): void
    {
        $this->seedAll();

        $this->getJson('/psgc/regions')
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1);
    }
}
