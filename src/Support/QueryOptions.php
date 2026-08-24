<?php

namespace Schoolees\Psgc\Support;

final class QueryOptions
{
    /**
     * @param array<int, string> $allowedOrderBy
     * @return array{0: string, 1: string} [$orderBy, $sortBy]
     */
    public static function normalizeSort(?string $orderBy, ?string $sortBy, array $allowedOrderBy): array
    {
        $defaultOrderBy = (string) config('psgc.order_by', 'name');
        $defaultSortBy  = (string) config('psgc.sort_by', 'asc');

        if ($allowedOrderBy !== [] && ! in_array($defaultOrderBy, $allowedOrderBy, true)) {
            $defaultOrderBy = $allowedOrderBy[0];
        }

        $sortBy = strtolower(trim((string) ($sortBy ?? '')));
        if (! in_array($sortBy, ['asc', 'desc'], true)) {
            $sortBy = strtolower($defaultSortBy) === 'desc' ? 'desc' : 'asc';
        }

        $orderBy = trim((string) ($orderBy ?? ''));
        if ($orderBy === '') {
            $orderBy = $defaultOrderBy;
        }

        // Prevent unexpected identifier input; fall back to configured default.
        if ($allowedOrderBy !== [] && ! in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = $defaultOrderBy;
        }

        return [$orderBy, $sortBy];
    }

    public static function normalizeLimit(?int $limit): int
    {
        $default = (int) config('psgc.paginate', 10);
        $max     = (int) config('psgc.max_limit', 100);

        $limit = (int) ($limit ?? 0);
        if ($limit <= 0) {
            $limit = $default > 0 ? $default : 10;
        }

        if ($max > 0 && $limit > $max) {
            $limit = $max;
        }

        return $limit;
    }

    public static function normalizeOffset(int $offset): int
    {
        return max(0, (int) $offset);
    }

    /**
     * Resolve the row offset to start from.
     *
     * `page` wins when supplied so that the paginator's own `links` are
     * followable; `offset` is honoured exactly, not rounded to a page boundary.
     */
    public static function resolveOffset(?int $page, ?int $offset, int $limit): int
    {
        if ($page !== null && $page > 0) {
            return ($page - 1) * max(1, $limit);
        }

        return self::normalizeOffset((int) ($offset ?? 0));
    }

    public static function pageFromOffset(int $offset, int $limit): int
    {
        if ($limit <= 0) {
            return 1;
        }

        return intdiv(max(0, $offset), $limit) + 1;
    }

    /**
     * Read a query parameter as an int, or null when it is absent, blank,
     * non-numeric, or an array (e.g. `?limit[]=5`).
     */
    public static function intOrNull(mixed $value): ?int
    {
        if (! is_scalar($value) || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Read a query parameter as a string, or null when it is absent or an array
     * (e.g. `?order_by[]=name`), which would otherwise be a TypeError.
     */
    public static function stringOrNull(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        return (string) $value;
    }

    /**
     * Escape LIKE metacharacters (\, %, _) so filter values are matched literally.
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
