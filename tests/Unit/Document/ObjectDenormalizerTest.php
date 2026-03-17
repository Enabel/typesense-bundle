<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Document;

use Enabel\Typesense\Document\ObjectDenormalizer;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Metadata\FieldMetadata;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Enabel\Typesense\Tests\Fixtures\ProductWithComputedFields;
use Enabel\Typesense\Tests\Fixtures\StringStatus;
use Enabel\Typesense\Tests\Fixtures\ValidProduct;
use Enabel\Typesense\Type\BackedEnumType;
use Enabel\Typesense\Type\BoolType;
use Enabel\Typesense\Type\DateTimeType;
use Enabel\Typesense\Type\FloatType;
use Enabel\Typesense\Type\IntType;
use Enabel\Typesense\Type\StringType;
use PHPUnit\Framework\TestCase;

final class ObjectDenormalizerTest extends TestCase
{
    private ObjectDenormalizer $denormalizer;

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

        $this->denormalizer = new ObjectDenormalizer($registry);
    }

    public function testItCreatesAnObjectFromADocument(): void
    {
        $timestamp = (new \DateTimeImmutable('2025-01-15 12:00:00'))->getTimestamp();

        $documents = [
            [
                'id' => '42',
                'title' => 'Widget',
                'price' => 9.99,
                'inStock' => true,
                'tags' => ['gadget', 'sale'],
                'popularity' => 100,
                'description' => 'A great widget',
                'subtitle' => 'Best seller',
                'createdAt' => $timestamp,
                'status' => 'active',
            ],
        ];

        $result = $this->denormalizer->denormalize($documents, ValidProduct::class);

        self::assertCount(1, $result);
        $product = $result[0];
        self::assertInstanceOf(ValidProduct::class, $product);
        self::assertSame(42, $product->id);
        self::assertSame('Widget', $product->title);
        self::assertSame(9.99, $product->price);
        self::assertTrue($product->inStock);
        self::assertSame(['gadget', 'sale'], $product->tags);
        self::assertSame(100, $product->popularity);
        self::assertSame('A great widget', $product->description);
        self::assertSame('Best seller', $product->subtitle);
        self::assertSame($timestamp, $product->createdAt->getTimestamp());
        self::assertSame(StringStatus::Active, $product->status);
    }

    public function testItSetsNullForMissingOptionalFields(): void
    {
        $documents = [
            [
                'id' => '1',
                'title' => 'Test',
                'price' => 1.0,
                'inStock' => false,
                'tags' => [],
                'popularity' => 0,
                'description' => 'Desc',
                'createdAt' => time(),
                'status' => 'inactive',
            ],
        ];

        $result = $this->denormalizer->denormalize($documents, ValidProduct::class);

        self::assertNull($result[0]->subtitle);
    }

    public function testItDenormalizesMultipleDocuments(): void
    {
        $now = time();
        $documents = [
            [
                'id' => '1',
                'title' => 'One',
                'price' => 1.0,
                'inStock' => true,
                'tags' => [],
                'popularity' => 10,
                'description' => 'First',
                'createdAt' => $now,
                'status' => 'active',
            ],
            [
                'id' => '2',
                'title' => 'Two',
                'price' => 2.0,
                'inStock' => false,
                'tags' => ['new'],
                'popularity' => 20,
                'description' => 'Second',
                'subtitle' => 'Sub',
                'createdAt' => $now,
                'status' => 'inactive',
            ],
        ];

        $result = $this->denormalizer->denormalize($documents, ValidProduct::class);

        self::assertCount(2, $result);
        self::assertSame(1, $result[0]->id);
        self::assertSame(2, $result[1]->id);
        self::assertSame('Sub', $result[1]->subtitle);
    }

    public function testItReturnsEmptyArrayForEmptyInput(): void
    {
        $result = $this->denormalizer->denormalize([], ValidProduct::class);

        self::assertSame([], $result);
    }

    public function testItThrowsOnMissingRequiredField(): void
    {
        $documents = [
            [
                'id' => '1',
                'title' => 'Test',
                // 'price' is missing and non-optional
                'inStock' => true,
                'tags' => [],
                'popularity' => 0,
                'description' => 'Desc',
                'createdAt' => time(),
                'status' => 'active',
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing required field "price"');

        $this->denormalizer->denormalize($documents, ValidProduct::class);
    }

    public function testItSkipsNonBackedFieldsDuringDenormalization(): void
    {
        $metadata = new DocumentMetadata(
            collection: 'products_computed',
            className: ProductWithComputedFields::class,
            idProperty: 'id',
            idType: new IntType(),
            fields: [
                new FieldMetadata(name: 'title', source: 'title', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new StringType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'subtitle', source: 'subtitle', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new StringType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'product_category', source: 'category', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: true, type: new StringType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'fullTitle', source: 'fullTitle', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: false, type: new StringType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'searchKeywords', source: 'searchKeywords', sourceType: FieldMetadata::SOURCE_METHOD, denormalize: false, type: new StringType(array: true), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata(name: 'internalCode', source: 'internalCode', sourceType: FieldMetadata::SOURCE_PROPERTY, denormalize: false, type: new StringType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
            ],
        );

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $registry->method('get')->willReturn($metadata);

        $denormalizer = new ObjectDenormalizer($registry);

        $documents = [
            [
                'id' => '1',
                'title' => 'Widget',
                'subtitle' => 'Deluxe',
                'product_category' => 'Electronics',
                'fullTitle' => 'Widget - Deluxe',
                'searchKeywords' => ['Widget', 'Electronics'],
                'internalCode' => 'W-001',
            ],
        ];

        $result = $denormalizer->denormalize($documents, ProductWithComputedFields::class);

        self::assertCount(1, $result);
        $product = $result[0];
        self::assertSame(1, $product->id);
        self::assertSame('Widget', $product->title);
        self::assertSame('Deluxe', $product->subtitle);
        self::assertSame('Electronics', $product->category);
        self::assertSame('Widget - Deluxe', $product->fullTitle);
    }

    public function testItUsesCustomFieldNameAsDocumentKey(): void
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

        $denormalizer = new ObjectDenormalizer($registry);

        $documents = [
            [
                'id' => '1',
                'product_category' => 'Electronics',
            ],
        ];

        $result = $denormalizer->denormalize($documents, ProductWithComputedFields::class);

        self::assertSame('Electronics', $result[0]->category);
    }

    public function testNormalizeAndDenormalizeRoundTrip(): void
    {
        $registry = $this->createMock(MetadataRegistryInterface::class);
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
        $registry->method('get')->willReturn($metadata);

        $normalizer = new \Enabel\Typesense\Document\DocumentNormalizer($registry);
        $denormalizer = new ObjectDenormalizer($registry);

        $product = new ValidProduct();
        $product->id = 42;
        $product->title = 'Widget';
        $product->price = 9.99;
        $product->inStock = true;
        $product->tags = ['gadget', 'sale'];
        $product->popularity = 100;
        $product->description = 'A great widget';
        $product->subtitle = null;
        $product->createdAt = new \DateTimeImmutable('2025-01-15 12:00:00');
        $product->status = StringStatus::Active;

        $documents = $normalizer->normalize([$product]);
        $objects = $denormalizer->denormalize($documents, ValidProduct::class);

        self::assertCount(1, $objects);
        $result = $objects[0];
        self::assertSame($product->id, $result->id);
        self::assertSame($product->title, $result->title);
        self::assertSame($product->price, $result->price);
        self::assertSame($product->inStock, $result->inStock);
        self::assertSame($product->tags, $result->tags);
        self::assertSame($product->popularity, $result->popularity);
        self::assertSame($product->description, $result->description);
        self::assertNull($result->subtitle);
        self::assertSame($product->createdAt->getTimestamp(), $result->createdAt->getTimestamp());
        self::assertSame($product->status, $result->status);
    }
}
