<?php

declare(strict_types=1);

namespace Enabel\Typesense\Metadata;

use Symfony\Contracts\Cache\CacheInterface;

final readonly class CachedMetadataRegistry implements MetadataRegistryInterface
{
    public function __construct(
        private MetadataRegistryInterface $inner,
        private CacheInterface $cache,
    ) {}

    public function get(string $className): DocumentMetadata
    {
        /** @var DocumentMetadata */
        return $this->cache->get(
            'typesense_metadata_' . md5($className),
            fn () => $this->inner->get($className),
        );
    }
}
