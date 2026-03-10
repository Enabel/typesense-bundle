<?php

declare(strict_types=1);

namespace Enabel\Typesense\Search\Response;

final class Response
{
    /** @var object[] */
    public array $documents {
        get => array_map(fn(Hit $h) => $h->document, $this->hits);
    }

    public int $totalPages {
        get => ($perPage = $this->raw['request_params']['per_page'] ?? 0) > 0
            ? (int) ceil($this->found / $perPage) : 0;
    }

    /**
     * @param Hit[] $hits
     * @param array<string, FacetCount> $facetCounts
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly int $found,
        public readonly int $outOf,
        public readonly int $page,
        public readonly int $searchTimeMs,
        public readonly bool $searchCutoff,
        public readonly array $hits,
        public readonly array $facetCounts,
        public readonly array $raw = [],
    ) {}
}
