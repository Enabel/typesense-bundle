<?php

declare(strict_types=1);

namespace Enabel\Typesense\Metadata;

final class MetadataRegistry implements MetadataRegistryInterface
{
    /** @var array<class-string, DocumentMetadata> */
    private array $cache = [];

    public function __construct(
        private readonly MetadataReaderInterface $reader,
    ) {}

    public function get(string $className): DocumentMetadata
    {
        return $this->cache[$className] ??= $this->reader->read($className);
    }
}
