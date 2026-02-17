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

    public static function pageFromOffset(int $offset, int $limit): int
    {
        if ($limit <= 0) {
            return 1;
        }

        return intdiv(max(0, $offset), $limit) + 1;
    }
}

