<?php

namespace Schoolees\Psgc\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Schoolees\Psgc\Http\Resources\RegionResources;
use Schoolees\Psgc\Services\RegionService;
use Schoolees\Psgc\Support\Utility;
use Throwable;

class RegionController
{
    public function __construct(protected RegionService $service) {}

    public function index(): array|JsonResponse
    {
        try {
            $collection = $this->service->getRegions(
                request()->all(),
                request()->input('order_by', 'name'),
                request()->input('sort_by', 'asc'),
                (int) request()->input('limit', (int) config('psgc.paginate', 10)),
                (int) request()->input('offset', 0)
            );

            return Utility::dataTableResponse(RegionResources::collection($collection));
        } catch (Throwable $e) {
            return Utility::jsonException($e);
        }
    }
}
