<?php

namespace Schoolees\Psgc\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Schoolees\Psgc\Pagination\OffsetPaginator;
use Schoolees\Psgc\Support\PsgcCache;
use Schoolees\Psgc\Support\QueryOptions;

abstract class SearchablePsgcService
{
    protected function paginateSearchable(
        Model $model,
        array $where,
        array $allowedOrderBy,
        ?string $orderBy = null,
        ?string $sortBy = null,
        ?int $limit = null,
        int $offset = 0,
        ?int $page = null
    ): LengthAwarePaginator {
        [$orderBy, $sortBy] = QueryOptions::normalizeSort($orderBy, $sortBy, $allowedOrderBy);
        $limit  = QueryOptions::normalizeLimit($limit);
        $offset = QueryOptions::resolveOffset($page, $offset, $limit);

        $searchable = method_exists($model, 'getSearchable')
            ? $model->getSearchable()
            : ['query' => [], 'query_like' => []];

        $query = $model->newQuery();

        $applied = [];

        foreach ($this->filtersFor($model, $where, $searchable['query'] ?? []) as $column => $value) {
            $query->where($column, $value);
            $applied[$column] = $value;
        }

        // LIKE is case-insensitive on MySQL but case-sensitive on PostgreSQL,
        // which has ILIKE for the case-insensitive match callers expect.
        $operator = $model->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        foreach ($this->filtersFor($model, $where, $searchable['query_like'] ?? []) as $column => $value) {
            $applied[$column] = $value;
            $pattern = '%' . QueryOptions::escapeLike((string) $value) . '%';

            // $column always comes from the model's own getSearchable() whitelist, never user input.
            // Each filter is its own AND condition: passing two filters narrows, never widens.
            $query->whereRaw("{$column} {$operator} ? ESCAPE ?", [$pattern, '\\']);
        }

        $cacheKey = PsgcCache::enabled()
            ? PsgcCache::key($model::class, [
                'filters' => $applied,
                'order'   => [$orderBy, $sortBy],
                'limit'   => $limit,
                'offset'  => $offset,
            ])
            : null;

        return $this->paginateAtOffset($query, $orderBy, $sortBy, $limit, $offset, $cacheKey)
            ->withAppliedFilters($applied);
    }

    /**
     * Look a single record up by its PSGC code.
     */
    protected function findByCode(Model $model, string $code): ?Model
    {
        $fetch = fn () => $model->newQuery()->find($code);

        if (! PsgcCache::enabled()) {
            return $fetch();
        }

        return PsgcCache::remember(PsgcCache::key($model::class, ['code' => $code]), $fetch);
    }

    /**
     * Reduce raw request input to the filters that are safe to put in a query.
     *
     * Values that cannot be bound as-is are dropped rather than applied, so a
     * malformed parameter never becomes a 500 or a silently inverted match.
     *
     * @param  array<int, string>  $columns
     * @return array<string, scalar>
     */
    protected function filtersFor(Model $model, array $where, array $columns): array
    {
        $casts   = $model->getCasts();
        $filters = [];

        foreach (array_intersect_key($where, array_flip($columns)) as $column => $value) {
            // e.g. `?name[]=a&name[]=b` — an array cannot be cast to string or bound.
            if (! is_scalar($value)) {
                continue;
            }

            // A blank parameter (an unfilled form field) is not a filter.
            if ($value === '') {
                continue;
            }

            $cast = $casts[$column] ?? null;

            if ($cast === 'bool' || $cast === 'boolean') {
                // `?is_city=true` arrives as the string "true", which compares as 0
                // against a boolean column on MySQL and matches nothing on SQLite.
                $value = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

                if ($value === null) {
                    continue;
                }
            }

            $filters[$column] = $value;
        }

        return $filters;
    }

    /**
     * Paginate from an exact row offset, rather than snapping to a page boundary.
     */
    protected function paginateAtOffset(
        Builder $query,
        string $orderBy,
        string $sortBy,
        int $limit,
        int $offset,
        ?string $cacheKey = null
    ): OffsetPaginator {
        $fetch = function () use ($query, $orderBy, $sortBy, $limit, $offset): array {
            $total = (clone $query)->toBase()->getCountForPagination();

            $items = $offset < $total
                ? $query->orderBy($orderBy, $sortBy)->offset($offset)->limit($limit)->get()
                : $query->getModel()->newCollection();

            return ['total' => $total, 'items' => $items];
        };

        $payload = $cacheKey !== null
            ? PsgcCache::remember($cacheKey, $fetch)
            : $fetch();

        $paginator = new OffsetPaginator($payload['items'], $payload['total'], $limit, $offset, [
            'path'     => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);

        // `offset` is dropped from generated links: they are page-based, and a stale
        // offset alongside `page` would be ambiguous.
        return $paginator->appends(Arr::except(request()->query(), ['page', 'offset']));
    }
}
