<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Type;

use Enabel\Typesense\Type\FloatType;
use PHPUnit\Framework\TestCase;

final class FloatTypeTest extends TestCase
{
    public function testItReturnsFloatAsTypeName(): void
    {
        self::assertSame('float', (new FloatType())->name);
    }

    public function testItReturnsFloatArrayAsTypeNameWhenArrayIsTrue(): void
    {
        self::assertSame('float[]', (new FloatType(array: true))->name);
    }

    public function testItNormalizesAValueToFloat(): void
    {
        self::assertSame(3.14, (new FloatType())->normalize('3.14'));
    }

    public function testItNormalizesAnArrayOfValuesToFloats(): void
    {
        self::assertSame([1.0, 2.5], (new FloatType(array: true))->normalize([1, 2.5]));
    }

    public function testItDenormalizesAFloatValue(): void
    {
        self::assertSame(3.14, (new FloatType())->denormalize(3.14));
    }
}
