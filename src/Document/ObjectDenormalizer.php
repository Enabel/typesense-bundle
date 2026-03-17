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
            $reflection->getProperty($metadata->idProperty)->setValue($object, $idValue);

            foreach ($metadata->fields as $field) {
                if (!$field->denormalize) {
                    continue;
                }

                if (!array_key_exists($field->name, $document)) {
                    if ($field->optional) {
                        $reflection->getProperty($field->source)->setValue($object, null);

                        continue;
                    }

                    throw new \RuntimeException(\sprintf(
                        'Missing required field "%s" in Typesense document for class "%s".',
                        $field->name,
                        $className,
                    ));
                }

                $value = $field->type->denormalize($document[$field->name]);
                $reflection->getProperty($field->source)->setValue($object, $value);
            }

            $result[] = $object;
        }

        return $result;
    }
}
