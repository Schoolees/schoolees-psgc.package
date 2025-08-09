<?php

namespace Schoolees\Psgc\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Schoolees\Psgc\Http\Resources\ProvinceResource;
use Schoolees\Psgc\Services\ProvinceService;
use Schoolees\Psgc\Support\Utility;

class ProvinceController
{
    public function __construct(protected ProvinceService $service) {}

    public function show(): array|JsonResponse
    {
        try {
            $collection = $this->service->getProvinces(
                request()->all(),
                request()->input('order_by', 'name'),
                request()->input('sort_by', 'asc'),
                (int) request()->input('limit', (int) config('psgc.paginate', 10)),
                (int) request()->input('offset', 0)
            );

            return Utility::dataTableResponse(ProvinceResource::collection($collection));
        } catch (Exception $e) {
            return Utility::jsonException($e);
        }
    }
}
