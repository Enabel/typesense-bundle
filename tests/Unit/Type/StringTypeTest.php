<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Type;

use Enabel\Typesense\Type\StringType;
use PHPUnit\Framework\TestCase;

final class StringTypeTest extends TestCase
{
    public function testItReturnsStringAsTypeName(): void
    {
        self::assertSame('string', (new StringType())->name);
    }

    public function testItReturnsStringArrayAsTypeNameWhenArrayIsTrue(): void
    {
        self::assertSame('string[]', (new StringType(array: true))->name);
    }

    public function testItNormalizesAValueToString(): void
    {
        self::assertSame('42', (new StringType())->normalize(42));
    }

    public function testItNormalizesAnArrayOfValuesToStrings(): void
    {
        self::assertSame(['1', '2'], (new StringType(array: true))->normalize([1, 2]));
    }

    public function testItDenormalizesAStringValue(): void
    {
        self::assertSame('hello', (new StringType())->denormalize('hello'));
    }

    public function testItDenormalizesAnArrayOfStrings(): void
    {
        self::assertSame(['a', 'b'], (new StringType(array: true))->denormalize(['a', 'b']));
    }
}
