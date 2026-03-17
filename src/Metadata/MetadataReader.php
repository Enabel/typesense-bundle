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
    public function read(string $className): DocumentMetadata
    {
        $class = new \ReflectionClass($className);
        
        $document = $this->readDocument($class);

        $id = null;

        foreach ($class->getProperties() as $property) {
            if ($this->readAttributes($property, Id::class) !== []) {
                if ($id !== null) {
                    throw new MappingException(\sprintf(
                        'Class %s has multiple #[Id] properties: %s, %s',
                        $className,
                        $id->getName(),
                        $property->getName(),
                    ));
                }
                $id = $property;
            }
        }

        if ($id === null) {
            throw new MappingException(\sprintf(
                'Class %s has no #[Id] property',
                $className,
            ));
        }

        $idAttr = $this->readAttributes($id, Id::class)[0];
        $idType = $idAttr->type ?? $this->inferType($id->getType(), $className, $id->getName());

        return new DocumentMetadata(
            collection: $document->collection,
            className: $className,
            idProperty: $id->getName(),
            idType: $idType,
            fields: $this->buildFieldMetadata($class),
            defaultSortingField: $document->defaultSortingField,
        );
    }

    /**
     * @param \ReflectionClass<object> $class
     */
    private function readDocument(\ReflectionClass $class): Document
    {
        $attrs = $this->readAttributes($class, Document::class);

        if (count($attrs) === 0) {
            throw new MappingException(sprintf(
                'Class %s is not a Typesense document (missing #[Document] attribute)',
                $class->getName(),
            ));
        }

        return $attrs[0];
    }

    /**
     * @template T of object
     *
     * @param \ReflectionClass<object>|\ReflectionProperty|\ReflectionMethod $reflector
     * @param class-string<T> $attributeClass
     *
     * @return list<T>
     */
    private function readAttributes(
        \ReflectionClass|\ReflectionProperty|\ReflectionMethod $reflector,
        string $attributeClass,
    ): array {
        return array_map(
            static fn (\ReflectionAttribute $attr) => $attr->newInstance(),
            $reflector->getAttributes($attributeClass),
        );
    }

    /**
     * @param \ReflectionClass<object> $class
     *
     * @return list<FieldMetadata>
     */
    private function buildFieldMetadata(\ReflectionClass $class): array
    {
        $className = $class->getName();
        $fields = [];

        foreach ($class->getProperties() as $property) {
            $fieldAttrs = $this->readAttributes($property, Field::class);
            if ($fieldAttrs !== []) {
                $fieldAttr = $fieldAttrs[0];

                $fields[] = $this->createFieldMetadata(
                    $fieldAttr,
                    $property->getType(),
                    $property->getName(),
                    $className,
                    FieldMetadata::SOURCE_PROPERTY,
                    $fieldAttr->denormalize ?? !$property->isVirtual(),
                );
            }
        }

        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $fieldAttrs = $this->readAttributes($method, Field::class);
            if ($fieldAttrs === []) {
                continue;
            }

            $fieldAttr = $fieldAttrs[0];

            $fields[] = $this->createFieldMetadata(
                $fieldAttr,
                $method->getReturnType(),
                $method->getName(),
                $className,
                FieldMetadata::SOURCE_METHOD,
                $fieldAttr->denormalize ?? false,
            );
        }

        return $fields;
    }

    private function createFieldMetadata(
        Field $fieldAttr,
        ?\ReflectionType $reflectionType,
        string $memberName,
        string $className,
        string $sourceType,
        bool $denormalize,
    ): FieldMetadata {
        return new FieldMetadata(
            name: $fieldAttr->name ?? $memberName,
            source: $memberName,
            sourceType: $sourceType,
            denormalize: $denormalize,
            type: $fieldAttr->type ?? $this->inferType($reflectionType, $className, $memberName),
            facet: $fieldAttr->facet,
            sort: $fieldAttr->sort,
            index: $fieldAttr->index,
            store: $fieldAttr->store,
            optional: $fieldAttr->optional || ($reflectionType instanceof \ReflectionNamedType && $reflectionType->allowsNull()),
            infix: $fieldAttr->infix,
        );
    }

    private function inferType(?\ReflectionType $type, string $className, string $memberName): TypeInterface
    {
        if (!$type instanceof \ReflectionNamedType) {
            throw new MappingException(\sprintf(
                '%s::%s has no type declaration — add a type or explicit type parameter',
                $className,
                $memberName,
            ));
        }

        $typeName = $type->getName();

        return match (true) {
            $typeName === 'string' => new StringType(),
            $typeName === 'int' => new IntType(),
            $typeName === 'float' => new FloatType(),
            $typeName === 'bool' => new BoolType(),
            $typeName === 'array' => throw new MappingException(\sprintf(
                '%s::%s is an array — explicit type required on #[Field]',
                $className,
                $memberName,
            )),
            is_subclass_of($typeName, \DateTimeInterface::class) => new DateTimeType(),
            is_subclass_of($typeName, \BackedEnum::class) => new BackedEnumType($typeName),
            is_subclass_of($typeName, \UnitEnum::class) => throw new MappingException(\sprintf(
                '%s::%s uses a non-backed enum — only BackedEnum is supported',
                $className,
                $memberName,
            )),
            default => throw new MappingException(\sprintf(
                '%s::%s has unsupported type "%s" — add an explicit type parameter on #[Field]',
                $className,
                $memberName,
                $typeName,
            )),
        };
    }
}
