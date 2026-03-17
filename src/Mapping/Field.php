<?php

declare(strict_types=1);

namespace Enabel\Typesense\Mapping;

use Enabel\Typesense\Type\TypeInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final readonly class Field
{
    /**
     * @param ?string $name Typesense field name (defaults to PHP property name)
     * @param ?TypeInterface $type PHP ↔ Typesense value converter (inferred when omitted)
     * @param bool $facet Enable faceting
     * @param bool $sort Enable sorting
     * @param bool $index Index in memory for search/sort/filter/facet
     * @param bool $store Store the field value in Typesense
     * @param bool $infix Enable infix (substring) search
     * @param bool $optional Allow empty, null or missing values
     * @param ?bool $denormalize Denormalize back to the object (defaults to false for virtual/method fields)
     */
    public function __construct(
        public ?string $name = null,
        public ?TypeInterface $type = null,
        public bool $facet = false,
        public bool $sort = false,
        public bool $index = true,
        public bool $store = true,
        public bool $infix = false,
        public bool $optional = false,
        public ?bool $denormalize = null,
    ) {}
}
