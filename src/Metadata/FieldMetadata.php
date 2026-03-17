<?php

declare(strict_types=1);

namespace Enabel\Typesense\Metadata;

use Enabel\Typesense\Type\TypeInterface;

final readonly class FieldMetadata
{
    public const string SOURCE_PROPERTY = 'property';
    public const string SOURCE_METHOD = 'method';

    public function __construct(
        public string $name,
        public string $source,
        public string $sourceType,
        public bool $denormalize,
        public TypeInterface $type,
        public bool $facet,
        public bool $sort,
        public bool $index,
        public bool $store,
        public bool $optional,
        public bool $infix,
    ) {}
}
