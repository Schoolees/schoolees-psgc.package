<?php

namespace Schoolees\Psgc\Pagination;

use Illuminate\Pagination\LengthAwarePaginator;
use Schoolees\Psgc\Support\QueryOptions;

/**
 * A LengthAwarePaginator that knows its true row offset.
 *
 * The base paginator derives `from`/`to` from the current page, which is wrong
 * whenever `?offset=` is not a multiple of the page size. Reporting the real
 * offset keeps `meta.from`/`meta.to` honest while leaving the generated `links`
 * page-based, as API consumers expect.
 */
class OffsetPaginator extends LengthAwarePaginator
{
    protected int $rowOffset;

    /** @var array<string, scalar> */
    protected array $appliedFilters = [];

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>|array<int, mixed>  $items
     */
    public function __construct($items, int $total, int $perPage, int $offset, array $options = [])
    {
        $this->rowOffset = max(0, $offset);

        parent::__construct(
            $items,
            $total,
            $perPage,
            QueryOptions::pageFromOffset($this->rowOffset, $perPage),
            $options
        );
    }

    public function rowOffset(): int
    {
        return $this->rowOffset;
    }

    /**
     * Record the filters that actually reached the query, so the response can
     * echo those rather than the raw query string.
     *
     * @param  array<string, scalar>  $filters
     */
    public function withAppliedFilters(array $filters): static
    {
        $this->appliedFilters = $filters;

        return $this;
    }

    /**
     * @return array<string, scalar>
     */
    public function appliedFilters(): array
    {
        return $this->appliedFilters;
    }

    public function firstItem()
    {
        return count($this->items) > 0 ? $this->rowOffset + 1 : null;
    }

    public function lastItem()
    {
        return count($this->items) > 0 ? $this->rowOffset + $this->count() : null;
    }
}
