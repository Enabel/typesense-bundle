<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Type;

use Enabel\Typesense\Type\BoolType;
use PHPUnit\Framework\TestCase;

final class BoolTypeTest extends TestCase
{
    public function testItReturnsBoolAsTypeName(): void
    {
        self::assertSame('bool', (new BoolType())->name);
    }

    public function testItReturnsBoolArrayAsTypeNameWhenArrayIsTrue(): void
    {
        self::assertSame('bool[]', (new BoolType(array: true))->name);
    }

    public function testItNormalizesIntegersToBooleans(): void
    {
        self::assertTrue((new BoolType())->normalize(1));
        self::assertFalse((new BoolType())->normalize(0));
    }

    public function testItNormalizesAnArrayOfIntegersToBooleans(): void
    {
        self::assertSame([true, false], (new BoolType(array: true))->normalize([1, 0]));
    }

    public function testItDenormalizesABooleanValue(): void
    {
        self::assertTrue((new BoolType())->denormalize(true));
    }
}
