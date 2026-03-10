<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Type;

use Enabel\Typesense\Type\DateTimeType;
use PHPUnit\Framework\TestCase;

final class DateTimeTypeTest extends TestCase
{
    public function testItReturnsInt64AsTypeName(): void
    {
        self::assertSame('int64', (new DateTimeType())->name);
    }

    public function testItReturnsInt64ArrayAsTypeNameWhenArrayIsTrue(): void
    {
        self::assertSame('int64[]', (new DateTimeType(array: true))->name);
    }

    public function testItNormalizesADateTimeToTimestamp(): void
    {
        $date = new \DateTimeImmutable('2024-01-15 12:00:00');
        self::assertSame($date->getTimestamp(), (new DateTimeType())->normalize($date));
    }

    public function testItNormalizesAnArrayOfDateTimesToTimestamps(): void
    {
        $d1 = new \DateTimeImmutable('2024-01-01');
        $d2 = new \DateTimeImmutable('2024-06-01');
        $result = (new DateTimeType(array: true))->normalize([$d1, $d2]);
        self::assertSame([$d1->getTimestamp(), $d2->getTimestamp()], $result);
    }

    public function testItDenormalizesATimestampToDateTimeImmutable(): void
    {
        $timestamp = 1705320000;
        $result = (new DateTimeType())->denormalize($timestamp);
        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame($timestamp, $result->getTimestamp());
    }

    public function testItDenormalizesAnArrayOfTimestampsToDateTimeImmutables(): void
    {
        $timestamps = [1705320000, 1717200000];
        $result = (new DateTimeType(array: true))->denormalize($timestamps);
        self::assertCount(2, $result);
        self::assertSame($timestamps[0], $result[0]->getTimestamp());
        self::assertSame($timestamps[1], $result[1]->getTimestamp());
    }

    public function testItCastsADateTimeToTimestamp(): void
    {
        $date = new \DateTimeImmutable('2024-01-15 12:00:00');
        self::assertSame($date->getTimestamp(), DateTimeType::cast($date));
    }
}
