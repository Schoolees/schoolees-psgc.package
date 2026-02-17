<?php

namespace Schoolees\Psgc\Services;

use Schoolees\Psgc\Models\Barangay;
use Schoolees\Psgc\Support\QueryOptions;

class BarangayService
{
    public function __construct(protected Barangay $model) {}

    public function getBarangays(array $where, ?string $orderBy = null, ?string $sortBy = null, ?int $limit = null, int $offset = 0)
    {
        [$orderBy, $sortBy] = QueryOptions::normalizeSort(
            $orderBy,
            $sortBy,
            ['code', 'name', 'city_code', 'created_at', 'updated_at']
        );
        $limit = QueryOptions::normalizeLimit($limit);
        $offset = QueryOptions::normalizeOffset($offset);

        $s = method_exists($this->model, 'getSearchable') ? $this->model->getSearchable() : ['query'=>[],'query_like'=>[]];
        $param     = array_intersect_key($where, array_flip($s['query']));
        $paramLike = array_intersect_key($where, array_flip($s['query_like']));

        $raw = $this->model->newQuery()->where($param)->where(function ($q) use ($paramLike) {
            foreach ($paramLike as $col => $val) {
                $q->orWhere($col, 'like', "%{$val}%");
            }
        });

        return $raw->orderBy($orderBy, $sortBy)
            ->paginate($limit, ['*'], 'page', QueryOptions::pageFromOffset($offset, $limit))
            ->withQueryString();
    }
}
