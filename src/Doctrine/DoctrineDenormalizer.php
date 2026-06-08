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
        $idProperty = $metadata->idProperty;

        $ids = array_map(
            fn(array $doc) => $metadata->idType->denormalize($doc['id']),
            $documents,
        );

        $entities = $this->entityManager->getRepository($className)->findBy([$idProperty => $ids]);

        // Index entities by their normalized (scalar) ID for O(1) lookup.
        // The raw id property may be an object (e.g. Symfony Ulid), which cannot be
        // used as an array key, so we key by the normalized id cast to string.
        $indexed = [];
        foreach ($entities as $entity) {
            $idValue = (new \ReflectionProperty($entity, $idProperty))->getValue($entity);
            $normalizedId = $metadata->idType->normalize($idValue);
            assert(!is_array($normalizedId));
            $indexed[(string) $normalizedId] = $entity;
        }

        // Map back in input order (using the Typesense document id), null for missing
        return array_map(
            fn(array $doc) => $indexed[(string) $doc['id']] ?? null,
            $documents,
        );
    }
}
