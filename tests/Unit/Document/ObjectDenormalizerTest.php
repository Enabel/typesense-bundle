<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Document;

use Enabel\Typesense\Document\ObjectDenormalizer;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Metadata\FieldMetadata;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
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
            className: ValidProduct::class,
            collection: 'products',
            defaultSortingField: 'popularity',
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [
                new FieldMetadata('title', new StringType(), facet: true, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata('price', new FloatType(), facet: false, sort: true, index: true, store: true, optional: false, infix: false),
                new FieldMetadata('inStock', new BoolType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata('tags', new StringType(array: true), facet: true, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata('popularity', new IntType(), facet: false, sort: true, index: false, store: true, optional: false, infix: false),
                new FieldMetadata('description', new StringType(), facet: false, sort: false, index: true, store: true, optional: false, infix: true),
                new FieldMetadata('subtitle', new StringType(), facet: false, sort: false, index: true, store: true, optional: true, infix: false),
                new FieldMetadata('createdAt', new DateTimeType(), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
                new FieldMetadata('status', new BackedEnumType(StringStatus::class), facet: false, sort: false, index: true, store: true, optional: false, infix: false),
            ],
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
}
