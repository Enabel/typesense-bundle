<?php

declare(strict_types=1);

namespace Enabel\Typesense\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Enabel\Typesense\Document\DenormalizerInterface;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;

final readonly class DoctrineDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MetadataRegistryInterface $registry,
    ) {}

    public function denormalize(array $documents, string $className): array
    {
        if ($documents === []) {
            return [];
        }

        $metadata = $this->registry->get($className);
        $idProperty = $metadata->idPropertyName;

        $ids = array_map(
            fn(array $doc) => $metadata->idType->denormalize($doc['id']),
            $documents,
        );

        $entities = $this->entityManager->getRepository($className)->findBy([$idProperty => $ids]);

        // Index entities by their ID for O(1) lookup
        $indexed = [];
        foreach ($entities as $entity) {
            $id = (new \ReflectionProperty($entity, $idProperty))->getValue($entity);
            $indexed[$id] = $entity;
        }

        // Map back in input order, null for missing
        return array_map(
            fn(mixed $id) => $indexed[$id] ?? null,
            $ids,
        );
    }
}
