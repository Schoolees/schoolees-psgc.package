<?php

namespace Schoolees\Psgc\Support;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Utility
{
    public static function dataTableResponse($collection): array
    {
        if (class_exists(\App\Libraries\UtilityLibrary::class)
            && method_exists(\App\Libraries\UtilityLibrary::class, 'dataTableResponse')) {
            return \App\Libraries\UtilityLibrary::dataTableResponse($collection);
        }

        return [
            'code'            => 200,
            'draw'            => (int) (request()->input('draw', 0) === 0 ? 0 : (int) request()->input('draw') + 1),
            'recordsFiltered' => $collection->total(),
            'recordsTotal'    => $collection->total(),
            'recordsPerPage'  => $collection->perPage(),
            'data'            => $collection,
            'filters'         => request()->all(),
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

        return response()->json(['code' => $status, 'error' => $e->getMessage()], $status);
    }
}
