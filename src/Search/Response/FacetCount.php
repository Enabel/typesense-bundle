<?php

declare(strict_types=1);

namespace Enabel\Typesense\Search\Response;

final readonly class FacetCount
{
    /**
     * @param FacetValue[] $counts
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $fieldName,
        public array $counts,
        public ?FacetStats $stats,
        public bool $sampled,
        public array $raw = [],
    ) {}
}
