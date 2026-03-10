<?php

declare(strict_types=1);

namespace Enabel\Typesense\Metadata;

use Enabel\Typesense\Type\TypeInterface;

final readonly class FieldMetadata
{
    public function __construct(
        public string $propertyName,
        public TypeInterface $type,
        public bool $facet,
        public bool $sort,
        public bool $index,
        public bool $store,
        public bool $optional,
        public bool $infix,
    ) {}
}
