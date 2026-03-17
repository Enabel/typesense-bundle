<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Metadata;

use Enabel\Typesense\Exception\MappingException;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Metadata\FieldMetadata;
use Enabel\Typesense\Metadata\MetadataReader;
use Enabel\Typesense\Tests\Fixtures\ArrayWithoutTypeClass;
use Enabel\Typesense\Tests\Fixtures\MultipleIdClass;
use Enabel\Typesense\Tests\Fixtures\NoDocumentClass;
use Enabel\Typesense\Tests\Fixtures\NoIdClass;
use Enabel\Typesense\Tests\Fixtures\NoTypeDeclarationClass;
use Enabel\Typesense\Tests\Fixtures\NonBackedEnumClass;
use Enabel\Typesense\Tests\Fixtures\ProductWithComputedFields;
use Enabel\Typesense\Tests\Fixtures\ValidProduct;
use Enabel\Typesense\Type\BackedEnumType;
use Enabel\Typesense\Type\BoolType;
use Enabel\Typesense\Type\DateTimeType;
use Enabel\Typesense\Type\FloatType;
use Enabel\Typesense\Type\IntType;
use Enabel\Typesense\Type\StringType;
use PHPUnit\Framework\TestCase;

final class MetadataReaderTest extends TestCase
{
    private MetadataReader $reader;

    protected function setUp(): void
    {
        $this->reader = new MetadataReader();
    }

    public function testItReadsAValidDocumentMetadata(): void
    {
        $metadata = $this->reader->read(ValidProduct::class);

        self::assertInstanceOf(DocumentMetadata::class, $metadata);
        self::assertSame(ValidProduct::class, $metadata->className);
        self::assertSame('products', $metadata->collection);
        self::assertSame('popularity', $metadata->defaultSortingField);
    }

    public function testItReadsTheIdProperty(): void
    {
        $metadata = $this->reader->read(ValidProduct::class);

        self::assertSame('id', $metadata->idProperty);
        self::assertInstanceOf(IntType::class, $metadata->idType);
    }

    public function testItInfersFieldTypesFromPropertyTypes(): void
    {
        $metadata = $this->reader->read(ValidProduct::class);
        $fields = $this->indexByName($metadata);

        self::assertInstanceOf(StringType::class, $fields['title']->type);
        self::assertInstanceOf(FloatType::class, $fields['price']->type);
        self::assertInstanceOf(BoolType::class, $fields['inStock']->type);
        self::assertInstanceOf(IntType::class, $fields['popularity']->type);
        self::assertInstanceOf(DateTimeType::class, $fields['createdAt']->type);
        self::assertInstanceOf(BackedEnumType::class, $fields['status']->type);
    }

    public function testItReadsExplicitArrayTypeFromFieldAttribute(): void
    {
        $metadata = $this->reader->read(ValidProduct::class);
        $fields = $this->indexByName($metadata);

        self::assertInstanceOf(StringType::class, $fields['tags']->type);
        self::assertSame('string[]', $fields['tags']->type->name);
    }

    public function testItReadsFieldOptionsFromAttribute(): void
    {
        $metadata = $this->reader->read(ValidProduct::class);
        $fields = $this->indexByName($metadata);

        self::assertTrue($fields['title']->facet);
        self::assertFalse($fields['title']->sort);
        self::assertTrue($fields['title']->index);
        self::assertTrue($fields['title']->store);
        self::assertFalse($fields['title']->infix);

        self::assertTrue($fields['price']->sort);
        self::assertFalse($fields['popularity']->index);
        self::assertTrue($fields['tags']->facet);
        self::assertTrue($fields['description']->infix);
    }

    public function testItSetsOptionalTrueForNullableProperties(): void
    {
        $metadata = $this->reader->read(ValidProduct::class);
        $fields = $this->indexByName($metadata);

        self::assertTrue($fields['subtitle']->optional);
        self::assertFalse($fields['title']->optional);
    }

    public function testItSetsSourceAndDenormalizeForBackedProperties(): void
    {
        $metadata = $this->reader->read(ValidProduct::class);
        $fields = $this->indexByName($metadata);

        self::assertSame('title', $fields['title']->source);
        self::assertSame(FieldMetadata::SOURCE_PROPERTY, $fields['title']->sourceType);
        self::assertTrue($fields['title']->denormalize);

        self::assertSame('price', $fields['price']->source);
        self::assertSame(FieldMetadata::SOURCE_PROPERTY, $fields['price']->sourceType);
        self::assertTrue($fields['price']->denormalize);
    }

    public function testItUsesCustomNameFromFieldAttribute(): void
    {
        $metadata = $this->reader->read(ProductWithComputedFields::class);
        $fields = $this->indexByName($metadata);

        self::assertSame('product_category', $fields['product_category']->name);
        self::assertSame('category', $fields['product_category']->source);
        self::assertSame(FieldMetadata::SOURCE_PROPERTY, $fields['product_category']->sourceType);
        self::assertTrue($fields['product_category']->denormalize);
    }

    public function testItDisablesDenormalizeForVirtualProperties(): void
    {
        $metadata = $this->reader->read(ProductWithComputedFields::class);
        $fields = $this->indexByName($metadata);

        self::assertSame('fullTitle', $fields['fullTitle']->source);
        self::assertSame(FieldMetadata::SOURCE_PROPERTY, $fields['fullTitle']->sourceType);
        self::assertFalse($fields['fullTitle']->denormalize);
    }

    public function testItSetsSourceTypeMethodForMethods(): void
    {
        $metadata = $this->reader->read(ProductWithComputedFields::class);
        $fields = $this->indexByName($metadata);

        self::assertSame('searchKeywords', $fields['searchKeywords']->source);
        self::assertSame(FieldMetadata::SOURCE_METHOD, $fields['searchKeywords']->sourceType);
        self::assertFalse($fields['searchKeywords']->denormalize);
        self::assertInstanceOf(StringType::class, $fields['searchKeywords']->type);
        self::assertSame('string[]', $fields['searchKeywords']->type->name);
    }

    public function testItDisablesDenormalizeWhenExplicitlyFalse(): void
    {
        $metadata = $this->reader->read(ProductWithComputedFields::class);
        $fields = $this->indexByName($metadata);

        self::assertSame('internalCode', $fields['internalCode']->source);
        self::assertSame(FieldMetadata::SOURCE_PROPERTY, $fields['internalCode']->sourceType);
        self::assertFalse($fields['internalCode']->denormalize);
    }

    public function testItInfersReturnTypeFromMethods(): void
    {
        $metadata = $this->reader->read(ProductWithComputedFields::class);
        $fields = $this->indexByName($metadata);

        self::assertInstanceOf(StringType::class, $fields['fullTitle']->type);
    }

    public function testItThrowsWhenDocumentAttributeIsMissing(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Class ' . NoDocumentClass::class . ' is not a Typesense document (missing #[Document] attribute)');

        $this->reader->read(NoDocumentClass::class);
    }

    public function testItThrowsWhenIdPropertyIsMissing(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Class ' . NoIdClass::class . ' has no #[Id] property');

        $this->reader->read(NoIdClass::class);
    }

    public function testItThrowsWhenMultipleIdPropertiesExist(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Class ' . MultipleIdClass::class . ' has multiple #[Id] properties: id, otherId');

        $this->reader->read(MultipleIdClass::class);
    }

    public function testItThrowsWhenArrayFieldHasNoExplicitType(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(ArrayWithoutTypeClass::class . '::tags is an array — explicit type required on #[Field]');

        $this->reader->read(ArrayWithoutTypeClass::class);
    }

    public function testItThrowsWhenFieldUsesANonBackedEnum(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(NonBackedEnumClass::class . '::status uses a non-backed enum — only BackedEnum is supported');

        $this->reader->read(NonBackedEnumClass::class);
    }

    public function testItThrowsWhenPropertyHasNoTypeDeclaration(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(NoTypeDeclarationClass::class . '::title has no type declaration — add a type or explicit type parameter');

        $this->reader->read(NoTypeDeclarationClass::class);
    }

    /**
     * @return array<string, \Enabel\Typesense\Metadata\FieldMetadata>
     */
    private function indexByName(DocumentMetadata $metadata): array
    {
        $indexed = [];
        foreach ($metadata->fields as $field) {
            $indexed[$field->name] = $field;
        }

        return $indexed;
    }
}
