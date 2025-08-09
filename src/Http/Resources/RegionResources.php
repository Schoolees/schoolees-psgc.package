<?php

namespace Schoolees\Psgc\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResources extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code'       => $this->code,
            'name'       => $this->name,
            'short_name' => $this->short_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
