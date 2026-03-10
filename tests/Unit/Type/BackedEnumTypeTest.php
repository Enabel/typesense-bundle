<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Type;

use Enabel\Typesense\Tests\Fixtures\IntStatus;
use Enabel\Typesense\Tests\Fixtures\StringStatus;
use Enabel\Typesense\Type\BackedEnumType;
use PHPUnit\Framework\TestCase;

final class BackedEnumTypeTest extends TestCase
{
    public function testItReturnsStringAsTypeNameForStringBackedEnums(): void
    {
        self::assertSame('string', (new BackedEnumType(StringStatus::class))->name);
    }

    public function testItReturnsInt64AsTypeNameForIntBackedEnums(): void
    {
        self::assertSame('int64', (new BackedEnumType(IntStatus::class))->name);
    }

    public function testItReturnsArrayTypeNameWhenArrayIsTrue(): void
    {
        self::assertSame('string[]', (new BackedEnumType(StringStatus::class, array: true))->name);
    }

    public function testItNormalizesAStringEnumToItsValue(): void
    {
        self::assertSame('active', (new BackedEnumType(StringStatus::class))->normalize(StringStatus::Active));
    }

    public function testItNormalizesAnIntEnumToItsValue(): void
    {
        self::assertSame(1, (new BackedEnumType(IntStatus::class))->normalize(IntStatus::Active));
    }

    public function testItNormalizesAnArrayOfEnumsToTheirValues(): void
    {
        $result = (new BackedEnumType(StringStatus::class, array: true))->normalize([StringStatus::Active, StringStatus::Inactive]);
        self::assertSame(['active', 'inactive'], $result);
    }

    public function testItDenormalizesAStringValueToAnEnum(): void
    {
        self::assertSame(StringStatus::Active, (new BackedEnumType(StringStatus::class))->denormalize('active'));
    }

    public function testItDenormalizesAnIntValueToAnEnum(): void
    {
        self::assertSame(IntStatus::Active, (new BackedEnumType(IntStatus::class))->denormalize(1));
    }

    public function testItDenormalizesAnArrayOfValuesToEnums(): void
    {
        $result = (new BackedEnumType(StringStatus::class, array: true))->denormalize(['active', 'inactive']);
        self::assertSame([StringStatus::Active, StringStatus::Inactive], $result);
    }

    public function testItCastsAnEnumToItsBackingValue(): void
    {
        self::assertSame('active', BackedEnumType::cast(StringStatus::Active));
        self::assertSame(1, BackedEnumType::cast(IntStatus::Active));
    }
}
