<?php

declare(strict_types=1);

namespace Enabel\Typesense\Search\Response;

final readonly class FacetValue
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $value,
        public int $count,
        public string $highlighted,
        public array $raw = [],
    ) {}
}
