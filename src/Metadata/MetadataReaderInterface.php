<?php

declare(strict_types=1);

namespace Enabel\Typesense\Metadata;

interface MetadataReaderInterface
{
    /**
     * @param class-string $className
     */
    public function read(string $className): DocumentMetadata;
}
