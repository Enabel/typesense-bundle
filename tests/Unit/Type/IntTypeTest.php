<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Type;

use Enabel\Typesense\Type\IntType;
use PHPUnit\Framework\TestCase;

final class IntTypeTest extends TestCase
{
    public function testItReturnsInt64AsDefaultTypeName(): void
    {
        self::assertSame('int64', (new IntType())->name);
    }

    public function testItReturnsInt32AsTypeNameWhenInt32IsTrue(): void
    {
        self::assertSame('int32', (new IntType(int32: true))->name);
    }

    public function testItReturnsInt64ArrayAsTypeNameWhenArrayIsTrue(): void
    {
        self::assertSame('int64[]', (new IntType(array: true))->name);
    }

    public function testItReturnsInt32ArrayAsTypeNameWhenBothFlagsAreTrue(): void
    {
        self::assertSame('int32[]', (new IntType(int32: true, array: true))->name);
    }

    public function testItNormalizesAValueToInteger(): void
    {
        self::assertSame(42, (new IntType())->normalize('42'));
    }

    public function testItNormalizesAnArrayOfValuesToIntegers(): void
    {
        self::assertSame([1, 2], (new IntType(array: true))->normalize(['1', '2']));
    }

    public function testItDenormalizesAnIntegerValue(): void
    {
        self::assertSame(42, (new IntType())->denormalize(42));
    }
}
