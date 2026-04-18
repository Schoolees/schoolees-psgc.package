<?php

namespace Schoolees\Psgc\Tests\Feature;

use Schoolees\Psgc\Models\Barangay;
use Schoolees\Psgc\Models\City;
use Schoolees\Psgc\Models\Province;
use Schoolees\Psgc\Models\Region;
use Schoolees\Psgc\Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    public function test_package_models_expose_expected_relationships_and_casts(): void
    {
        $region = Region::query()->create([
            'code' => '130000000',
            'name' => 'National Capital Region',
            'short_name' => 'NCR',
        ]);

        $province = Province::query()->create([
            'code' => '133900000',
            'name' => 'Metro Manila',
            'region_code' => $region->code,
        ]);

        $city = City::query()->create([
            'code' => '133900001',
            'name' => 'City of Manila',
            'region_code' => $region->code,
            'province_code' => $province->code,
            'is_city' => 1,
            'city_class' => 'HUC',
        ]);

        $barangay = Barangay::query()->create([
            'code' => '133900001001',
            'name' => 'Barangay 1',
            'city_code' => $city->code,
        ]);

        $this->assertTrue($city->fresh()->is_city);
        $this->assertTrue($region->provinces->contains($province));
        $this->assertTrue($region->cities->contains($city));
        $this->assertTrue($province->cities->contains($city));
        $this->assertSame($region->code, $city->region->code);
        $this->assertSame($province->code, $city->province->code);
        $this->assertTrue($city->barangays->contains($barangay));
        $this->assertSame($city->code, $barangay->city->code);
    }
}
