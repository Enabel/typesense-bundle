<?php

declare(strict_types=1);

namespace Enabel\Typesense;

use Enabel\Typesense\Document\DenormalizerInterface;
use Enabel\Typesense\Document\DocumentNormalizerInterface;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Enabel\Typesense\Schema\SchemaBuilderInterface;
use Typesense\Exceptions\ObjectAlreadyExists;

final readonly class Client implements ClientInterface
{
    /**
     * @param array<class-string, DenormalizerInterface> $denormalizers
     */
    public function __construct(
        private \Typesense\Client $typesenseClient,
        private MetadataRegistryInterface $registry,
        private DocumentNormalizerInterface $normalizer,
        private SchemaBuilderInterface $schemaBuilder,
        private array $denormalizers,
    ) {}

    /**
     * @param class-string $className
     * @throws \InvalidArgumentException
     */
    public function collection(string $className): Collection
    {
        $metadata = $this->registry->get($className);

        if (!isset($this->denormalizers[$className])) {
            throw new \InvalidArgumentException(\sprintf('No denormalizer registered for "%s"', $className));
        }

        return new Collection(
            $metadata,
            $this->typesenseClient->getCollections()[$metadata->collection],
            $this->denormalizers[$className],
            $this->normalizer,
        );
    }

    /**
     * @param class-string $className
     */
    public function create(string $className): void
    {
        $metadata = $this->registry->get($className);
        $schema = $this->schemaBuilder->build($metadata);

        $tsCollection = $this->typesenseClient->getCollections()[$metadata->collection];
        if ($tsCollection->exists()) {
            return;
        }

        try {
            $this->typesenseClient->getCollections()->create($schema);
        } catch (ObjectAlreadyExists) {
        }
    }

    /**
     * @param class-string $className
     */
    public function drop(string $className): void
    {
        $metadata = $this->registry->get($className);
        $this->typesenseClient->getCollections()[$metadata->collection]->delete();
    }
}
