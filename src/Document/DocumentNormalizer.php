<?php

declare(strict_types=1);

namespace Enabel\Typesense\Document;

use Enabel\Typesense\Metadata\MetadataRegistryInterface;

final readonly class DocumentNormalizer implements DocumentNormalizerInterface
{
    public function __construct(
        private MetadataRegistryInterface $registry,
    ) {}

    /**
     * @param object[] $objects
     * @return array<array<string, mixed>>
     */
    public function normalize(array $objects): array
    {
        if ($objects === []) {
            return [];
        }

        $metadata = $this->registry->get($objects[0]::class);
        $result = [];

        foreach ($objects as $object) {
            $reflection = new \ReflectionObject($object);
            $doc = [];

            $idValue = $metadata->idType->normalize(
                $reflection->getProperty($metadata->idPropertyName)->getValue($object),
            );
            assert(!is_array($idValue));
            $doc['id'] = (string) $idValue;

            foreach ($metadata->fields as $field) {
                $value = $reflection->getProperty($field->propertyName)->getValue($object);

                if ($value === null && $field->optional) {
                    continue;
                }

                $doc[$field->propertyName] = $field->type->normalize($value);
            }

            $result[] = $doc;
        }

        return $result;
    }
}
