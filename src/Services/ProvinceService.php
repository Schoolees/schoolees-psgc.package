<?php

namespace Schoolees\Psgc\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schoolees\Psgc\Models\Province;

class ProvinceService extends SearchablePsgcService
{
    public function __construct(protected Province $model) {}

    public function getProvinces(
        array $where,
        ?string $orderBy = null,
        ?string $sortBy = null,
        ?int $limit = null,
        int $offset = 0,
        ?int $page = null
    ): LengthAwarePaginator {
        return $this->paginateSearchable(
            $this->model,
            $where,
            ['code', 'name', 'region_code', 'created_at', 'updated_at'],
            $orderBy,
            $sortBy,
            $limit,
            $offset,
            $page
        );
    }
}
