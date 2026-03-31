<?php

declare(strict_types=1);

namespace Enabel\Typesense\Metadata;

use Enabel\Typesense\Mapping\Document;

final class MetadataRegistry implements MetadataRegistryInterface
{
    /** @var array<class-string, DocumentMetadata> */
    private array $cache = [];

    public function __construct(
        private readonly MetadataReaderInterface $reader,
    ) {}

    public function get(string $className): DocumentMetadata
    {
        return $this->cache[$className] ??= $this->reader->read($this->resolveClass($className));
    }

    /**
     * Resolves proxy/subclass names to the nearest ancestor bearing #[Document].
     *
     * @param class-string $className
     * @return class-string
     */
    private function resolveClass(string $className): string
    {
        $reflector = new \ReflectionClass($className);

        do {
            if ($reflector->getAttributes(Document::class) !== []) {
                return $reflector->getName();
            }
        } while ($reflector = $reflector->getParentClass());

        return $className;
    }
}
