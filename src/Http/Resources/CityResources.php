<?php

namespace Schoolees\Psgc\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code'          => $this->code,
            'name'          => $this->name,
            'region_code'   => $this->region_code,
            'province_code' => $this->province_code,
            'is_city'       => (bool) $this->is_city,
            'city_class'    => $this->city_class,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
