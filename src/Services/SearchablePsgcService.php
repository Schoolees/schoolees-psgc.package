<?php

namespace Schoolees\Psgc\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        int $offset = 0
    ): LengthAwarePaginator {
        [$orderBy, $sortBy] = QueryOptions::normalizeSort($orderBy, $sortBy, $allowedOrderBy);
        $limit = QueryOptions::normalizeLimit($limit);
        $offset = QueryOptions::normalizeOffset($offset);

        $searchable = method_exists($model, 'getSearchable')
            ? $model->getSearchable()
            : ['query' => [], 'query_like' => []];

        $query = $model->newQuery()->where(
            array_intersect_key($where, array_flip($searchable['query'] ?? []))
        );

        $likeFilters = array_intersect_key($where, array_flip($searchable['query_like'] ?? []));

        if ($likeFilters !== []) {
            $query->where(function (Builder $builder) use ($likeFilters): void {
                foreach ($likeFilters as $column => $value) {
                    $builder->orWhere($column, 'like', "%{$value}%");
                }
            });
        }

        return $query
            ->orderBy($orderBy, $sortBy)
            ->paginate($limit, ['*'], 'page', QueryOptions::pageFromOffset($offset, $limit))
            ->withQueryString();
    }
}
