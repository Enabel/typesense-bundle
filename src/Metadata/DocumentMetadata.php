<?php

declare(strict_types=1);

namespace Enabel\Typesense\Metadata;

use Enabel\Typesense\Type\TypeInterface;

final readonly class DocumentMetadata
{
    /**
     * @param class-string $className
     * @param FieldMetadata[] $fields
     */
    public function __construct(
        public string $className,
        public string $collection,
        public ?string $defaultSortingField,
        public string $idPropertyName,
        public TypeInterface $idType,
        public array $fields,
    ) {}
}
