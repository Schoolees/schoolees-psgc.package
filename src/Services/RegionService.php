<?php

namespace Schoolees\Psgc\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schoolees\Psgc\Models\Region;

class RegionService extends SearchablePsgcService
{
    public function __construct(protected Region $model) {}

    public function getRegions(
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
            ['code', 'name', 'short_name'],
            $orderBy,
            $sortBy,
            $limit,
            $offset,
            $page
        );
    }

    public function findRegion(string $code): ?Region
    {
        return $this->findByCode($this->model, $code);
    }
}
