<?php

namespace Schoolees\Psgc\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Schoolees\Psgc\Http\Resources\CityResources;
use Schoolees\Psgc\Services\CityService;
use Schoolees\Psgc\Support\Utility;

class CityController
{
    public function __construct(protected CityService $service) {}

    public function show(): array|JsonResponse
    {
        try {
            $collection = $this->service->getCities(
                request()->all(),
                request()->input('order_by', 'name'),
                request()->input('sort_by', 'asc'),
                (int) request()->input('limit', (int) config('psgc.paginate', 10)),
                (int) request()->input('offset', 0)
            );

            return Utility::dataTableResponse(CityResource::collection($collection));
        } catch (Exception $e) {
            return Utility::jsonException($e);
        }
    }
}
