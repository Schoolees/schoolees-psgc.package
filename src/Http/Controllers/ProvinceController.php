<?php

namespace Schoolees\Psgc\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Schoolees\Psgc\Http\Resources\ProvinceResources;
use Schoolees\Psgc\Services\ProvinceService;
use Schoolees\Psgc\Support\QueryOptions;
use Schoolees\Psgc\Support\Utility;
use Throwable;

class ProvinceController
{
    public function __construct(protected ProvinceService $service) {}

    public function index(): array|JsonResponse
    {
        try {
            $request = request();

            $collection = $this->service->getProvinces(
                $request->all(),
                QueryOptions::stringOrNull($request->input('order_by')),
                QueryOptions::stringOrNull($request->input('sort_by')),
                QueryOptions::intOrNull($request->input('limit')),
                QueryOptions::intOrNull($request->input('offset')) ?? 0,
                QueryOptions::intOrNull($request->input('page'))
            );

            return Utility::dataTableResponse(ProvinceResources::collection($collection));
        } catch (Throwable $e) {
            return Utility::jsonException($e);
        }
    }
}
