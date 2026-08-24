<?php

namespace Schoolees\Psgc\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Schoolees\Psgc\Http\Resources\CityResources;
use Schoolees\Psgc\Services\CityService;
use Schoolees\Psgc\Support\QueryOptions;
use Schoolees\Psgc\Support\Utility;
use Throwable;

class CityController
{
    public function __construct(protected CityService $service) {}

    public function index(): array|JsonResponse
    {
        try {
            $request = request();

            $collection = $this->service->getCities(
                $request->all(),
                QueryOptions::stringOrNull($request->input('order_by')),
                QueryOptions::stringOrNull($request->input('sort_by')),
                QueryOptions::intOrNull($request->input('limit')),
                QueryOptions::intOrNull($request->input('offset')) ?? 0,
                QueryOptions::intOrNull($request->input('page'))
            );

            return Utility::dataTableResponse(CityResources::collection($collection));
        } catch (Throwable $e) {
            return Utility::jsonException($e);
        }
    }
}
