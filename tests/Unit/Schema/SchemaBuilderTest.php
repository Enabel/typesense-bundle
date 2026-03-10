<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Schema;

use Enabel\Typesense\Mapping\Infix;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Metadata\FieldMetadata;
use Enabel\Typesense\Schema\SchemaBuilder;
use Enabel\Typesense\Type\BoolType;
use Enabel\Typesense\Type\DateTimeType;
use Enabel\Typesense\Type\FloatType;
use Enabel\Typesense\Type\IntType;
use Enabel\Typesense\Type\StringType;
use PHPUnit\Framework\TestCase;

final class SchemaBuilderTest extends TestCase
{
    private SchemaBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new SchemaBuilder();
    }

    public function testItBuildsAMinimalSchema(): void
    {
        $metadata = new DocumentMetadata(
            className: 'App\Entity\Product',
            collection: 'products',
            defaultSortingField: null,
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [
                new FieldMetadata(
                    propertyName: 'title',
                    type: new StringType(),
                    facet: false,
                    sort: false,
                    index: true,
                    store: true,
                    optional: false,
                    infix: Infix::Off,
                ),
            ],
        );

        $schema = $this->builder->build($metadata);

        self::assertSame('products', $schema['name']);
        self::assertArrayNotHasKey('default_sorting_field', $schema);
        self::assertCount(1, $schema['fields']);
        self::assertSame([
            'name' => 'title',
            'type' => 'string',
        ], $schema['fields'][0]);
    }

    public function testItIncludesDefaultSortingFieldInSchema(): void
    {
        $metadata = new DocumentMetadata(
            className: 'App\Entity\Product',
            collection: 'products',
            defaultSortingField: 'popularity',
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [
                new FieldMetadata(
                    propertyName: 'popularity',
                    type: new IntType(),
                    facet: false,
                    sort: true,
                    index: true,
                    store: true,
                    optional: false,
                    infix: Infix::Off,
                ),
            ],
        );

        $schema = $this->builder->build($metadata);

        self::assertSame('popularity', $schema['default_sorting_field']);
    }

    public function testItIncludesNonDefaultFieldOptions(): void
    {
        $metadata = new DocumentMetadata(
            className: 'App\Entity\Product',
            collection: 'products',
            defaultSortingField: null,
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [
                new FieldMetadata(
                    propertyName: 'category',
                    type: new StringType(),
                    facet: true,
                    sort: true,
                    index: true,
                    store: true,
                    optional: false,
                    infix: Infix::Always,
                ),
                new FieldMetadata(
                    propertyName: 'internalNote',
                    type: new StringType(),
                    facet: false,
                    sort: false,
                    index: false,
                    store: false,
                    optional: true,
                    infix: Infix::Off,
                ),
            ],
        );

        $schema = $this->builder->build($metadata);

        self::assertSame([
            'name' => 'category',
            'type' => 'string',
            'facet' => true,
            'sort' => true,
            'infix' => true,
        ], $schema['fields'][0]);

        self::assertSame([
            'name' => 'internalNote',
            'type' => 'string',
            'index' => false,
            'store' => false,
            'optional' => true,
        ], $schema['fields'][1]);
    }

    public function testItHandlesArrayTypes(): void
    {
        $metadata = new DocumentMetadata(
            className: 'App\Entity\Product',
            collection: 'products',
            defaultSortingField: null,
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [
                new FieldMetadata(
                    propertyName: 'tags',
                    type: new StringType(array: true),
                    facet: true,
                    sort: false,
                    index: true,
                    store: true,
                    optional: false,
                    infix: Infix::Off,
                ),
            ],
        );

        $schema = $this->builder->build($metadata);

        self::assertSame([
            'name' => 'tags',
            'type' => 'string[]',
            'facet' => true,
        ], $schema['fields'][0]);
    }

    public function testItHandlesAllFieldTypes(): void
    {
        $metadata = new DocumentMetadata(
            className: 'App\Entity\Product',
            collection: 'products',
            defaultSortingField: null,
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [
                new FieldMetadata(
                    propertyName: 'title',
                    type: new StringType(),
                    facet: false,
                    sort: false,
                    index: true,
                    store: true,
                    optional: false,
                    infix: Infix::Off,
                ),
                new FieldMetadata(
                    propertyName: 'count',
                    type: new IntType(),
                    facet: false,
                    sort: false,
                    index: true,
                    store: true,
                    optional: false,
                    infix: Infix::Off,
                ),
                new FieldMetadata(
                    propertyName: 'smallCount',
                    type: new IntType(int32: true),
                    facet: false,
                    sort: false,
                    index: true,
                    store: true,
                    optional: false,
                    infix: Infix::Off,
                ),
                new FieldMetadata(
                    propertyName: 'price',
                    type: new FloatType(),
                    facet: false,
                    sort: false,
                    index: true,
                    store: true,
                    optional: false,
                    infix: Infix::Off,
                ),
                new FieldMetadata(
                    propertyName: 'active',
                    type: new BoolType(),
                    facet: false,
                    sort: false,
                    index: true,
                    store: true,
                    optional: false,
                    infix: Infix::Off,
                ),
                new FieldMetadata(
                    propertyName: 'createdAt',
                    type: new DateTimeType(),
                    facet: false,
                    sort: false,
                    index: true,
                    store: true,
                    optional: false,
                    infix: Infix::Off,
                ),
            ],
        );

        $schema = $this->builder->build($metadata);

        self::assertSame('string', $schema['fields'][0]['type']);
        self::assertSame('int64', $schema['fields'][1]['type']);
        self::assertSame('int32', $schema['fields'][2]['type']);
        self::assertSame('float', $schema['fields'][3]['type']);
        self::assertSame('bool', $schema['fields'][4]['type']);
        self::assertSame('int64', $schema['fields'][5]['type']);
    }

    public function testItExcludesTheIdField(): void
    {
        $metadata = new DocumentMetadata(
            className: 'App\Entity\Product',
            collection: 'products',
            defaultSortingField: null,
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [],
        );

        $schema = $this->builder->build($metadata);

        self::assertSame([], $schema['fields']);
    }

    public function testItSetsInfixTrueForFallbackMode(): void
    {
        $metadata = new DocumentMetadata(
            className: 'App\Entity\Product',
            collection: 'products',
            defaultSortingField: null,
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [
                new FieldMetadata(
                    propertyName: 'title',
                    type: new StringType(),
                    facet: false,
                    sort: false,
                    index: true,
                    store: true,
                    optional: false,
                    infix: Infix::Fallback,
                ),
            ],
        );

        $schema = $this->builder->build($metadata);

        self::assertTrue($schema['fields'][0]['infix']);
    }
}
