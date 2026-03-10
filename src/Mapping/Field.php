<?php

declare(strict_types=1);

namespace Enabel\Typesense\Mapping;

use Enabel\Typesense\Type\TypeInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Field
{
    public function __construct(
        public ?TypeInterface $type = null,
        public bool $facet = false,
        public bool $sort = false,
        public bool $index = true,
        public bool $store = true,
        public bool $infix = false,
        public bool $optional = false,
    ) {}
}
