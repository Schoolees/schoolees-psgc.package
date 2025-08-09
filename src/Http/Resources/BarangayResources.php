<?php

namespace Schoolees\Psgc\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code'       => $this->code,
            'name'       => $this->name,
            'city_code'  => $this->city_code,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
