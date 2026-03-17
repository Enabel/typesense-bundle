<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Document;

use Enabel\Typesense\Document\DocumentNormalizer;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Metadata\FieldMetadata;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Enabel\Typesense\Tests\Fixtures\ProductWithComputedFields;
use Enabel\Typesense\Tests\Fixtures\StringStatus;
use Enabel\Typesense\Tests\Fixtures\ValidProduct;
use Enabel\Typesense\Type\BoolType;
use Enabel\Typesense\Type\DateTimeType;
use Enabel\Typesense\Type\FloatType;
use Enabel\Typesense\Type\IntType;
use Enabel\Typesense\Type\BackedEnumType;
use Enabel\Typesense\Type\StringType;
use PHPUnit\Framework\TestCase;

final class DocumentNormalizerTest extends TestCase
{
    private DocumentNormalizer $normalizer;

    protected function setUp(): void
    {
        $metadata = new DocumentMetadata(
            collection: 'products',
            className: ValidProduct::class,
            idProperty: 'id',
            idType: new IntType(),
            fields: [
                new FieldMetadata(name: 'title', source: 'title', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new StringType(), facet: true, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'price', source: 'price', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new FloatType(), facet: false, sort: true, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'inStock', source: 'inStock', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new BoolType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'tags', source: 'tags', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new StringType(array: true), facet: true, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'popularity', source: 'popularity', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new IntType(), facet: false, sort: true, index: false, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'description', source: 'description', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new StringType(), facet: false, sort: false, index: true, store: true, optional: false, infix: true),
                new FieldMetadata(name: 'subtitle', source: 'subtitle', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new StringType(), facet: false, sort: false, index: true, store: true, optional: true, infix: false),
                new FieldMetadata(name: 'createdAt', source: 'createdAt', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new DateTimeType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'status', source: 'status', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new BackedEnumType(StringStatus::class), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
            ],
            defaultSortingField: 'popularity',
        );

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $registry->method('get')->willReturn($metadata);

        $this->normalizer = new DocumentNormalizer($registry);
    }

    public function testItConvertsAnObjectToADocument(): void
    {
        $product = new ValidProduct();
        $product->id = 42;
        $product->title = 'Widget';
        $product->price = 9.99;
        $product->inStock = true;
        $product->tags = ['gadget', 'sale'];
        $product->popularity = 100;
        $product->description = 'A great widget';
        $product->subtitle = 'Best seller';
        $product->createdAt = new \DateTimeImmutable('2025-01-15 12:00:00');
        $product->status = StringStatus::Active;

        $result = $this->normalizer->normalize([$product]);

        self::assertCount(1, $result);
        self::assertSame('42', $result[0]['id']);
        self::assertSame('Widget', $result[0]['title']);
        self::assertSame(9.99, $result[0]['price']);
        self::assertTrue($result[0]['inStock']);
        self::assertSame(['gadget', 'sale'], $result[0]['tags']);
        self::assertSame(100, $result[0]['popularity']);
        self::assertSame('A great widget', $result[0]['description']);
        self::assertSame('Best seller', $result[0]['subtitle']);
        self::assertSame($product->createdAt->getTimestamp(), $result[0]['createdAt']);
        self::assertSame('active', $result[0]['status']);
    }

    public function testItOmitsNullOptionalFields(): void
    {
        $product = new ValidProduct();
        $product->id = 1;
        $product->title = 'Test';
        $product->price = 1.0;
        $product->inStock = false;
        $product->tags = [];
        $product->popularity = 0;
        $product->description = 'Desc';
        $product->subtitle = null;
        $product->createdAt = new \DateTimeImmutable();
        $product->status = StringStatus::Inactive;

        $result = $this->normalizer->normalize([$product]);

        self::assertArrayNotHasKey('subtitle', $result[0]);
    }

    public function testItNormalizesMultipleObjects(): void
    {
        $product1 = new ValidProduct();
        $product1->id = 1;
        $product1->title = 'One';
        $product1->price = 1.0;
        $product1->inStock = true;
        $product1->tags = [];
        $product1->popularity = 10;
        $product1->description = 'First';
        $product1->subtitle = null;
        $product1->createdAt = new \DateTimeImmutable();
        $product1->status = StringStatus::Active;

        $product2 = new ValidProduct();
        $product2->id = 2;
        $product2->title = 'Two';
        $product2->price = 2.0;
        $product2->inStock = false;
        $product2->tags = ['new'];
        $product2->popularity = 20;
        $product2->description = 'Second';
        $product2->subtitle = 'Sub';
        $product2->createdAt = new \DateTimeImmutable();
        $product2->status = StringStatus::Inactive;

        $result = $this->normalizer->normalize([$product1, $product2]);

        self::assertCount(2, $result);
        self::assertSame('1', $result[0]['id']);
        self::assertSame('2', $result[1]['id']);
        self::assertSame('Sub', $result[1]['subtitle']);
    }

    public function testItReturnsEmptyArrayForEmptyInput(): void
    {
        $result = $this->normalizer->normalize([]);

        self::assertSame([], $result);
    }

    public function testItNormalizesVirtualPropertyFields(): void
    {
        $metadata = new DocumentMetadata(
            collection: 'products_computed',
            className: ProductWithComputedFields::class,
            idProperty: 'id',
            idType: new IntType(),
            fields: [
                new FieldMetadata(name: 'title', source: 'title', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new StringType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'subtitle', source: 'subtitle', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new StringType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'fullTitle', source: 'fullTitle', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: false, type: new StringType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
            ],
        );

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $registry->method('get')->willReturn($metadata);

        $normalizer = new DocumentNormalizer($registry);

        $product = new ProductWithComputedFields();
        $product->id = 1;
        $product->title = 'Widget';
        $product->subtitle = 'Deluxe';

        $result = $normalizer->normalize([$product]);

        self::assertSame('Widget - Deluxe', $result[0]['fullTitle']);
    }

    public function testItNormalizesMethodFields(): void
    {
        $metadata = new DocumentMetadata(
            collection: 'products_computed',
            className: ProductWithComputedFields::class,
            idProperty: 'id',
            idType: new IntType(),
            fields: [
                new FieldMetadata(name: 'title', source: 'title', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new StringType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'product_category', source: 'category', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new StringType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'searchKeywords', source: 'searchKeywords', sourceType: FieldMetadata::SOURCE_METHOD, denormalize: false, type: new StringType(array: true), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
            ],
        );

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $registry->method('get')->willReturn($metadata);

        $normalizer = new DocumentNormalizer($registry);

        $product = new ProductWithComputedFields();
        $product->id = 1;
        $product->title = 'Widget';
        $product->category = 'Electronics';

        $result = $normalizer->normalize([$product]);

        self::assertSame(['Widget', 'Electronics'], $result[0]['searchKeywords']);
    }

    public function testItUsesCustomFieldName(): void
    {
        $metadata = new DocumentMetadata(
            collection: 'products_computed',
            className: ProductWithComputedFields::class,
            idProperty: 'id',
            idType: new IntType(),
            fields: [
                new FieldMetadata(name: 'product_category', source: 'category', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new StringType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
            ],
        );

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $registry->method('get')->willReturn($metadata);

        $normalizer = new DocumentNormalizer($registry);

        $product = new ProductWithComputedFields();
        $product->id = 1;
        $product->category = 'Electronics';

        $result = $normalizer->normalize([$product]);

        self::assertSame('Electronics', $result[0]['product_category']);
        self::assertArrayNotHasKey('category', $result[0]);
    }
}
