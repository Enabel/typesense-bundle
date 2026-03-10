<?php

declare(strict_types=1);

namespace Enabel\Typesense\Document;

use Enabel\Typesense\Metadata\MetadataRegistryInterface;

final readonly class ObjectDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private MetadataRegistryInterface $registry,
    ) {}

    public function denormalize(array $documents, string $className): array
    {
        if ($documents === []) {
            return [];
        }

        $metadata = $this->registry->get($className);
        $reflection = new \ReflectionClass($className);
        $result = [];

        foreach ($documents as $document) {
            $object = $reflection->newInstanceWithoutConstructor();

            $idValue = $metadata->idType->denormalize($document['id']);
            $reflection->getProperty($metadata->idPropertyName)->setValue($object, $idValue);

            foreach ($metadata->fields as $field) {
                if (!array_key_exists($field->propertyName, $document)) {
                    if ($field->optional) {
                        $reflection->getProperty($field->propertyName)->setValue($object, null);
                    }

                    continue;
                }

                $value = $field->type->denormalize($document[$field->propertyName]);
                $reflection->getProperty($field->propertyName)->setValue($object, $value);
            }

            $result[] = $object;
        }

        return $result;
    }
}
