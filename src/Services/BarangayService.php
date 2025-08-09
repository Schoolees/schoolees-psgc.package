<?php

namespace Schoolees\Psgc\Services;

use Schoolees\Psgc\Models\Barangay;

class BarangayService
{
    public function __construct(protected Barangay $model) {}

    public function getBarangays(array $where, ?string $orderBy = null, ?string $sortBy = null, ?int $limit = null, int $offset = 0)
    {
        $orderBy = $orderBy ?: config('psgc.order_by', 'name');
        $sortBy  = $sortBy  ?: config('psgc.sort_by', 'asc');
        $limit   = $limit   ?: (int) config('psgc.paginate', 10);

        $s = method_exists($this->model, 'getSearchable') ? $this->model->getSearchable() : ['query'=>[],'query_like'=>[]];
        $param     = array_intersect_key($where, array_flip($s['query']));
        $paramLike = array_intersect_key($where, array_flip($s['query_like']));

        $raw = $this->model::where($param)->where(function ($q) use ($paramLike) {
            foreach ($paramLike as $col => $val) $q->orWhere($col, 'like', "%{$val}%");
        });

        return $raw->orderBy($orderBy, $sortBy)
            ->paginate($limit, ['*'], 'page', (int)(($offset / $limit) + 1))
            ->withQueryString();
    }
}
