<?php

namespace Schoolees\Psgc\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Schoolees\Psgc\Http\Resources\BarangayResources;
use Schoolees\Psgc\Services\BarangayService;
use Schoolees\Psgc\Support\QueryOptions;
use Schoolees\Psgc\Support\Utility;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class BarangayController
{
    public function __construct(protected BarangayService $service) {}

    public function index(): array|JsonResponse
    {
        try {
            $request = request();

            $collection = $this->service->getBarangays(
                $request->all(),
                QueryOptions::stringOrNull($request->input('order_by')),
                QueryOptions::stringOrNull($request->input('sort_by')),
                QueryOptions::intOrNull($request->input('limit')),
                QueryOptions::intOrNull($request->input('offset')) ?? 0,
                QueryOptions::intOrNull($request->input('page'))
            );

            return Utility::dataTableResponse(BarangayResources::collection($collection));
        } catch (Throwable $e) {
            return Utility::jsonException($e);
        }
    }

    public function show(string $code): array|JsonResponse
    {
        try {
            $record = $this->service->findBarangay($code);

            if ($record === null) {
                throw new NotFoundHttpException("No barangay found for code [{$code}].");
            }

            return Utility::itemResponse(new BarangayResources($record));
        } catch (Throwable $e) {
            return Utility::jsonException($e);
        }
    }
}
