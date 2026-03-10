<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Mapping;

use Enabel\Typesense\Mapping\Document;
use PHPUnit\Framework\TestCase;

final class DocumentTest extends TestCase
{
    public function testItStoresCollectionAndDefaultSortingField(): void
    {
        $attr = new Document(collection: 'products', defaultSortingField: 'popularity');
        self::assertSame('products', $attr->collection);
        self::assertSame('popularity', $attr->defaultSortingField);
    }

    public function testItDefaultsToNullForDefaultSortingField(): void
    {
        $attr = new Document(collection: 'products');
        self::assertNull($attr->defaultSortingField);
    }

    public function testItTargetsClasses(): void
    {
        $ref = new \ReflectionClass(Document::class);
        $attrs = $ref->getAttributes(\Attribute::class);
        self::assertCount(1, $attrs);
        self::assertSame(\Attribute::TARGET_CLASS, $attrs[0]->newInstance()->flags);
    }
}
