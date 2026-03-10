<?php

declare(strict_types=1);

namespace Enabel\Typesense\Search\Response;

final readonly class GroupedHit
{
    /**
     * @param mixed[] $groupKey
     * @param Hit[] $hits
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public array $groupKey,
        public array $hits,
        public int $found,
        public array $raw = [],
    ) {}
}
