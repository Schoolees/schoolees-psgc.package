<?php

namespace Schoolees\Psgc\Support;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Utility
{
    public static function dataTableResponse($collection): array
    {
        if ((string) config('psgc.response_format', 'datatable') === 'pagination') {
            return self::paginationResponse($collection);
        }

        if (class_exists(\App\Libraries\UtilityLibrary::class)
            && method_exists(\App\Libraries\UtilityLibrary::class, 'dataTableResponse')) {
            return \App\Libraries\UtilityLibrary::dataTableResponse($collection);
        }

        return [
            'code'            => 200,
            'draw'            => (int) request()->input('draw', 0),
            'recordsFiltered' => $collection->total(),
            'recordsTotal'    => $collection->total(),
            'recordsPerPage'  => $collection->perPage(),
            'data'            => $collection,
            'filters'         => request()->all(),
        ];
    }

    public static function paginationResponse($collection): array
    {
        $payload = $collection->response()->getData(true);

        return [
            'data' => $payload['data'] ?? [],
            'meta' => $payload['meta'] ?? [],
            'links' => $payload['links'] ?? [],
            'filters' => request()->all(),
        ];
    }

    public static function jsonException(\Throwable $e): JsonResponse
    {
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
        } else {
            $code = (int) $e->getCode();
            $status = ($code >= 400 && $code <= 599) ? $code : 500;
        }

        $message = $status >= 500 && ! config('app.debug', false)
            ? 'Server Error'
            : $e->getMessage();

        return response()->json(['code' => $status, 'error' => $message], $status);
    }
}
