<?php

declare(strict_types=1);

namespace Enabel\Typesense\Search\Response;

final readonly class GroupedResponse
{
    /**
     * @param GroupedHit[] $groupedHits
     * @param array<string, FacetCount> $facetCounts
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $found,
        public int $foundDocs,
        public int $outOf,
        public int $page,
        public int $searchTimeMs,
        public bool $searchCutoff,
        public array $groupedHits,
        public array $facetCounts,
        public array $raw = [],
    ) {}
}
