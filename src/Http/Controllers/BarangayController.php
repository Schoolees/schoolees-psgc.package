<?php

namespace Schoolees\Psgc\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Schoolees\Psgc\Http\Resources\BarangayResources;
use Schoolees\Psgc\Services\BarangayService;
use Schoolees\Psgc\Support\Utility;
use Throwable;

class BarangayController
{
    public function __construct(protected BarangayService $service) {}

    public function index(): array|JsonResponse
    {
        try {
            $collection = $this->service->getBarangays(
                request()->all(),
                request()->input('order_by', 'name'),
                request()->input('sort_by', 'asc'),
                (int) request()->input('limit', (int) config('psgc.paginate', 10)),
                (int) request()->input('offset', 0)
            );

            return Utility::dataTableResponse(BarangayResources::collection($collection));
        } catch (Throwable $e) {
            return Utility::jsonException($e);
        }
    }
}
