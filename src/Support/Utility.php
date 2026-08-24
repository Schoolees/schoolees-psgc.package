<?php

namespace Schoolees\Psgc\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Schoolees\Psgc\Pagination\OffsetPaginator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Utility
{
    public static function dataTableResponse($collection): array
    {
        $formatter = config('psgc.response_formatter');

        if ($formatter !== null) {
            return self::callFormatter($formatter, $collection);
        }

        if ((string) config('psgc.response_format', 'datatable') === 'pagination') {
            return self::paginationResponse($collection);
        }

        if (self::legacyHostFormatter() !== null) {
            return \App\Libraries\UtilityLibrary::dataTableResponse($collection);
        }

        return [
            'code'            => 200,
            'draw'            => (int) request()->input('draw', 0),
            'recordsFiltered' => $collection->total(),
            'recordsTotal'    => $collection->total(),
            'recordsPerPage'  => $collection->perPage(),
            'data'            => $collection,
            'filters'         => self::filters($collection),
        ];
    }

    public static function paginationResponse($collection): array
    {
        $payload = $collection->response()->getData(true);

        return [
            'data' => $payload['data'] ?? [],
            'meta' => $payload['meta'] ?? [],
            'links' => $payload['links'] ?? [],
            'filters' => self::filters($collection),
        ];
    }

    /**
     * Invoke the configured `psgc.response_formatter`.
     *
     * Accepts a callable, 'Class@method', [Class::class, 'method'], or a class
     * name that is invokable or exposes dataTableResponse().
     */
    protected static function callFormatter(mixed $formatter, $collection): array
    {
        if (is_string($formatter) && ! is_callable($formatter)) {
            if (str_contains($formatter, '@')) {
                [$class, $method] = explode('@', $formatter, 2);
                $formatter = [app($class), $method];
            } elseif (class_exists($formatter)) {
                $instance = app($formatter);

                $formatter = method_exists($instance, 'dataTableResponse')
                    ? [$instance, 'dataTableResponse']
                    : $instance;
            }
        }

        if (! is_callable($formatter)) {
            throw new InvalidArgumentException(
                'psgc.response_formatter must be callable, "Class@method", or a class exposing dataTableResponse().'
            );
        }

        return (array) $formatter($collection);
    }

    /**
     * The pre-config host-app hook.
     *
     * @deprecated Superseded by the `psgc.response_formatter` config key. This
     *             fallback is kept so existing host apps keep their envelope on
     *             upgrade, and is scheduled for removal in 2.0.
     */
    protected static function legacyHostFormatter(): ?string
    {
        $legacy = \App\Libraries\UtilityLibrary::class;

        return class_exists($legacy) && method_exists($legacy, 'dataTableResponse')
            ? $legacy
            : null;
    }

    /**
     * The `filters` echo, per the `psgc.filters_echo` config key.
     */
    protected static function filters($collection): array
    {
        $mode = (string) config('psgc.filters_echo', 'request');

        if ($mode === 'none') {
            return [];
        }

        if ($mode === 'applied') {
            $paginator = $collection->resource ?? null;

            return $paginator instanceof OffsetPaginator
                ? $paginator->appliedFilters()
                : [];
        }

        return request()->all();
    }

    public static function jsonException(\Throwable $e): JsonResponse
    {
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
        } else {
            $code = (int) $e->getCode();
            $status = ($code >= 400 && $code <= 599) ? $code : 500;
        }

        // Otherwise an internal failure is converted to JSON and leaves no trace.
        if ($status >= 500 && config('psgc.log_exceptions', true)) {
            Log::error('PSGC request failed: ' . $e->getMessage(), [
                'exception' => $e,
                'url'       => request()->fullUrl(),
            ]);
        }

        $message = $status >= 500 && ! config('app.debug', false)
            ? 'Server Error'
            : $e->getMessage();

        return response()->json(['code' => $status, 'error' => $message], $status);
    }
}
