<?php

declare(strict_types=1);

namespace Enabel\Typesense\Mapping;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class Document
{
    public function __construct(
        public string $collection,
        public ?string $defaultSortingField = null,
    ) {}
}
