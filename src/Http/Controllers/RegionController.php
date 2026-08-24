<?php

namespace Schoolees\Psgc\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Schoolees\Psgc\Http\Resources\RegionResources;
use Schoolees\Psgc\Services\RegionService;
use Schoolees\Psgc\Support\QueryOptions;
use Schoolees\Psgc\Support\Utility;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class RegionController
{
    public function __construct(protected RegionService $service) {}

    public function index(): array|JsonResponse
    {
        try {
            $request = request();

            $collection = $this->service->getRegions(
                $request->all(),
                QueryOptions::stringOrNull($request->input('order_by')),
                QueryOptions::stringOrNull($request->input('sort_by')),
                QueryOptions::intOrNull($request->input('limit')),
                QueryOptions::intOrNull($request->input('offset')) ?? 0,
                QueryOptions::intOrNull($request->input('page'))
            );

            return Utility::dataTableResponse(RegionResources::collection($collection));
        } catch (Throwable $e) {
            return Utility::jsonException($e);
        }
    }

    public function show(string $code): array|JsonResponse
    {
        try {
            $record = $this->service->findRegion($code);

            if ($record === null) {
                throw new NotFoundHttpException("No region found for code [{$code}].");
            }

            return Utility::itemResponse(new RegionResources($record));
        } catch (Throwable $e) {
            return Utility::jsonException($e);
        }
    }
}
