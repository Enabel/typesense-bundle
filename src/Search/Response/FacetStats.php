<?php

declare(strict_types=1);

namespace Enabel\Typesense\Search\Response;

final readonly class FacetStats
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public int $totalValues,
        public ?float $min,
        public ?float $max,
        public ?float $sum,
        public ?float $avg,
        public array $raw = [],
    ) {}
}
