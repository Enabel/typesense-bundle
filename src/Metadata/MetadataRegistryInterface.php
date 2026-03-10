<?php

declare(strict_types=1);

namespace Enabel\Typesense\Metadata;

interface MetadataRegistryInterface
{
    /**
     * @param class-string $className
     */
    public function get(string $className): DocumentMetadata;
}
