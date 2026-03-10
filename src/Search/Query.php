<?php

declare(strict_types=1);

namespace Enabel\Typesense\Search;

final class Query
{
    private ?string $q;

    /** @var string[] */
    private array $queryByFields = [];

    private ?string $filterBy = null;

    /** @var string[] */
    private array $sorts = [];

    /** @var string[] */
    private array $facetByFields = [];

    private ?int $maxFacetValues = null;
    private ?int $page = null;
    private ?int $perPage = null;

    private function __construct(?string $q)
    {
        $this->q = $q;
    }

    public static function create(?string $q = null): self
    {
        return new self($q);
    }

    public function queryBy(string ...$fields): self
    {
        $this->queryByFields = $fields;

        return $this;
    }

    public function filterBy(Filter|string $filter): self
    {
        $this->filterBy = (string) $filter;

        return $this;
    }

    public function sortBy(string $field, Sort $direction = Sort::Asc): self
    {
        $this->sorts = [\sprintf('%s:%s', $field, $direction->value)];

        return $this;
    }

    public function thenSortBy(string $field, Sort $direction = Sort::Asc): self
    {
        $this->sorts[] = \sprintf('%s:%s', $field, $direction->value);

        return $this;
    }

    public function facetBy(string ...$fields): self
    {
        $this->facetByFields = $fields;

        return $this;
    }

    public function maxFacetValues(int $max): self
    {
        $this->maxFacetValues = $max;

        return $this;
    }

    public function page(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function perPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toParams(): array
    {
        $params = [];

        $params['q'] = $this->q ?? '*';

        if ($this->queryByFields !== []) {
            $params['query_by'] = implode(',', $this->queryByFields);
        }

        if ($this->filterBy !== null) {
            $params['filter_by'] = $this->filterBy;
        }

        if ($this->sorts !== []) {
            $params['sort_by'] = implode(',', $this->sorts);
        }

        if ($this->facetByFields !== []) {
            $params['facet_by'] = implode(',', $this->facetByFields);
        }

        if ($this->maxFacetValues !== null) {
            $params['max_facet_values'] = $this->maxFacetValues;
        }

        if ($this->page !== null) {
            $params['page'] = $this->page;
        }

        if ($this->perPage !== null) {
            $params['per_page'] = $this->perPage;
        }

        return $params;
    }
}
