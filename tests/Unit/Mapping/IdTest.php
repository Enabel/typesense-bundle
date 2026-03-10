<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Mapping;

use Enabel\Typesense\Mapping\Id;
use Enabel\Typesense\Type\StringType;
use PHPUnit\Framework\TestCase;

final class IdTest extends TestCase
{
    public function testItDefaultsTypeToNull(): void
    {
        $attr = new Id();
        self::assertNull($attr->type);
    }

    public function testItAcceptsAnExplicitType(): void
    {
        $type = new StringType();
        $attr = new Id(type: $type);
        self::assertSame($type, $attr->type);
    }

    public function testItTargetsProperties(): void
    {
        $ref = new \ReflectionClass(Id::class);
        $attrs = $ref->getAttributes(\Attribute::class);
        self::assertCount(1, $attrs);
        self::assertSame(\Attribute::TARGET_PROPERTY, $attrs[0]->newInstance()->flags);
    }
}
