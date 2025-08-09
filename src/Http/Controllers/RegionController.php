<?php

namespace Schoolees\Psgc\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Schoolees\Psgc\Http\Resources\RegionResources;
use Schoolees\Psgc\Services\RegionService;
use Schoolees\Psgc\Support\Utility;

class RegionController
{
    public function __construct(protected RegionService $service) {}

    public function show(): array|JsonResponse
    {
        try {
            $collection = $this->service->getRegions(
                request()->all(),
                request()->input('order_by', 'name'),
                request()->input('sort_by', 'asc'),
                (int) request()->input('limit', (int) config('psgc.paginate', 10)),
                (int) request()->input('offset', 0)
            );

            return Utility::dataTableResponse(RegionResource::collection($collection));
        } catch (Exception $e) {
            return Utility::jsonException($e);
        }
    }
}
