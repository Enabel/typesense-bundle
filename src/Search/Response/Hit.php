<?php

declare(strict_types=1);

namespace Enabel\Typesense\Search\Response;

final readonly class Hit
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public object $document,
        public int $textMatch,
        public array $raw = [],
    ) {}
}
