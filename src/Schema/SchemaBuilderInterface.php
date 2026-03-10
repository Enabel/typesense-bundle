<?php

declare(strict_types=1);

namespace Enabel\Typesense\Schema;

use Enabel\Typesense\Metadata\DocumentMetadata;

interface SchemaBuilderInterface
{
    /**
     * @return array{name: string, fields: list<array<string, mixed>>, default_sorting_field?: string}
     */
    public function build(DocumentMetadata $metadata): array;
}
