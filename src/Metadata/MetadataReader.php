<?php

declare(strict_types=1);

namespace Enabel\Typesense\Metadata;

use Enabel\Typesense\Exception\MappingException;
use Enabel\Typesense\Mapping\Document;
use Enabel\Typesense\Mapping\Field;
use Enabel\Typesense\Mapping\Id;
use Enabel\Typesense\Type\BackedEnumType;
use Enabel\Typesense\Type\BoolType;
use Enabel\Typesense\Type\DateTimeType;
use Enabel\Typesense\Type\FloatType;
use Enabel\Typesense\Type\IntType;
use Enabel\Typesense\Type\StringType;
use Enabel\Typesense\Type\TypeInterface;

final readonly class MetadataReader implements MetadataReaderInterface
{
    /**
     * @param class-string $className
     */
    public function read(string $className): DocumentMetadata
    {
        $reflection = new \ReflectionClass($className);

        $documentAttrs = $reflection->getAttributes(Document::class);
        if ($documentAttrs === []) {
            throw new MappingException(\sprintf(
                'Class %s is not a Typesense document (missing #[Document] attribute)',
                $className,
            ));
        }

        /** @var Document $document */
        $document = $documentAttrs[0]->newInstance();

        $idProperties = [];
        $fields = [];

        foreach ($reflection->getProperties() as $property) {
            $idAttrs = $property->getAttributes(Id::class);
            if ($idAttrs !== []) {
                $idProperties[] = $property;
            }

            $fieldAttrs = $property->getAttributes(Field::class);
            if ($fieldAttrs !== []) {
                /** @var Field $fieldAttr */
                $fieldAttr = $fieldAttrs[0]->newInstance();
                $type = $fieldAttr->type ?? $this->inferType($property, $className);
                $optional = $fieldAttr->optional || ($property->getType() instanceof \ReflectionNamedType && $property->getType()->allowsNull());

                $fields[] = new FieldMetadata(
                    propertyName: $property->getName(),
                    type: $type,
                    facet: $fieldAttr->facet,
                    sort: $fieldAttr->sort,
                    index: $fieldAttr->index,
                    store: $fieldAttr->store,
                    optional: $optional,
                    infix: $fieldAttr->infix,
                );
            }
        }

        if (\count($idProperties) === 0) {
            throw new MappingException(\sprintf(
                'Class %s has no #[Id] property',
                $className,
            ));
        }

        if (\count($idProperties) > 1) {
            throw new MappingException(\sprintf(
                'Class %s has multiple #[Id] properties: %s',
                $className,
                implode(', ', array_map(fn (\ReflectionProperty $p) => $p->getName(), $idProperties)),
            ));
        }

        $idProperty = $idProperties[0];
        /** @var Id $idAttr */
        $idAttr = $idProperty->getAttributes(Id::class)[0]->newInstance();
        $idType = $idAttr->type ?? $this->inferType($idProperty, $className);

        return new DocumentMetadata(
            className: $className,
            collection: $document->collection,
            defaultSortingField: $document->defaultSortingField,
            idPropertyName: $idProperty->getName(),
            idType: $idType,
            fields: $fields,
        );
    }

    private function inferType(\ReflectionProperty $property, string $className): TypeInterface
    {
        $type = $property->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new MappingException(\sprintf(
                'Property %s::%s has no type declaration — add a type or explicit type parameter',
                $className,
                $property->getName(),
            ));
        }

        $typeName = $type->getName();

        return match (true) {
            $typeName === 'string' => new StringType(),
            $typeName === 'int' => new IntType(),
            $typeName === 'float' => new FloatType(),
            $typeName === 'bool' => new BoolType(),
            $typeName === 'array' => throw new MappingException(\sprintf(
                'Property %s::%s is an array — explicit type required on #[Field]',
                $className,
                $property->getName(),
            )),
            is_subclass_of($typeName, \DateTimeInterface::class) => new DateTimeType(),
            is_subclass_of($typeName, \BackedEnum::class) => new BackedEnumType($typeName),
            is_subclass_of($typeName, \UnitEnum::class) => throw new MappingException(\sprintf(
                'Property %s::%s uses a non-backed enum — only BackedEnum is supported',
                $className,
                $property->getName(),
            )),
            default => throw new MappingException(\sprintf(
                'Property %s::%s has no type declaration — add a type or explicit type parameter',
                $className,
                $property->getName(),
            )),
        };
    }
}
