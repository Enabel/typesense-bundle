<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Mapping;

use Enabel\Typesense\Mapping\Field;
use Enabel\Typesense\Type\StringType;
use PHPUnit\Framework\TestCase;

final class FieldTest extends TestCase
{
    public function testItHasSensibleDefaults(): void
    {
        $attr = new Field();
        self::assertNull($attr->type);
        self::assertFalse($attr->facet);
        self::assertFalse($attr->sort);
        self::assertTrue($attr->index);
        self::assertTrue($attr->store);
        self::assertFalse($attr->infix);
        self::assertFalse($attr->optional);
    }

    public function testItAcceptsCustomValues(): void
    {
        $type = new StringType(array: true);
        $attr = new Field(
            type: $type,
            facet: true,
            sort: true,
            index: false,
            store: false,
            infix: true,
            optional: true,
        );
        self::assertSame($type, $attr->type);
        self::assertTrue($attr->facet);
        self::assertTrue($attr->sort);
        self::assertFalse($attr->index);
        self::assertFalse($attr->store);
        self::assertTrue($attr->infix);
        self::assertTrue($attr->optional);
    }

    public function testItTargetsProperties(): void
    {
        $ref = new \ReflectionClass(Field::class);
        $attrs = $ref->getAttributes(\Attribute::class);
        self::assertCount(1, $attrs);
        self::assertSame(\Attribute::TARGET_PROPERTY, $attrs[0]->newInstance()->flags);
    }
}
