<?php

namespace Schoolees\Psgc\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Schoolees\Psgc\Models\City;

class CityService extends SearchablePsgcService
{
    public function __construct(protected City $model) {}

    public function getCities(
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
            ['code', 'name', 'region_code', 'province_code', 'is_city', 'city_class'],
            $orderBy,
            $sortBy,
            $limit,
            $offset,
            $page
        );
    }

    public function findCity(string $code): ?City
    {
        return $this->findByCode($this->model, $code);
    }
}
