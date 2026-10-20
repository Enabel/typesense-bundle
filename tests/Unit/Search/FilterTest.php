<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Search;

use Enabel\Typesense\Search\Filter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FilterTest extends TestCase
{
    public function testItFormatsEqualsFilterWithString(): void
    {
        self::assertSame('category:=`electronics`', (string) Filter::equals('category', 'electronics'));
    }

    public function testItFormatsEqualsFilterWithInt(): void
    {
        self::assertSame('price:=100', (string) Filter::equals('price', 100));
    }

    public function testItFormatsEqualsFilterWithFloat(): void
    {
        self::assertSame('price:=9.99', (string) Filter::equals('price', 9.99));
    }

    public function testItFormatsEqualsFilterWithBoolTrue(): void
    {
        self::assertSame('in_stock:=true', (string) Filter::equals('in_stock', true));
    }

    public function testItFormatsEqualsFilterWithBoolFalse(): void
    {
        self::assertSame('in_stock:=false', (string) Filter::equals('in_stock', false));
    }

    public function testItFormatsNotEqualsFilter(): void
    {
        self::assertSame('status:!=`draft`', (string) Filter::notEquals('status', 'draft'));
    }

    public function testItFormatsMatchesFilter(): void
    {
        self::assertSame('name:wireless', (string) Filter::matches('name', 'wireless'));
    }

    public function testItFormatsGreaterThanFilter(): void
    {
        self::assertSame('price:>100', (string) Filter::greaterThan('price', 100));
    }

    public function testItFormatsGreaterThanOrEqualFilter(): void
    {
        self::assertSame('price:>=100', (string) Filter::greaterThanOrEqual('price', 100));
    }

    public function testItFormatsLessThanFilter(): void
    {
        self::assertSame('price:<50', (string) Filter::lessThan('price', 50));
    }

    public function testItFormatsLessThanOrEqualFilter(): void
    {
        self::assertSame('price:<=50', (string) Filter::lessThanOrEqual('price', 50));
    }

    public function testItFormatsInFilterWithStrings(): void
    {
        self::assertSame(
            'status:[`active`, `pending`]',
            (string) Filter::in('status', ['active', 'pending']),
        );
    }

    public function testItFormatsInFilterWithInts(): void
    {
        self::assertSame('id:[1, 2, 3]', (string) Filter::in('id', [1, 2, 3]));
    }

    public function testItFormatsNotInFilter(): void
    {
        self::assertSame(
            'status:!=[`archived`, `deleted`]',
            (string) Filter::notIn('status', ['archived', 'deleted']),
        );
    }

    public function testItFormatsBetweenFilterWithInts(): void
    {
        self::assertSame('price:[10..500]', (string) Filter::between('price', 10, 500));
    }

    public function testItFormatsBetweenFilterWithFloats(): void
    {
        self::assertSame('price:[1.5..9.99]', (string) Filter::between('price', 1.5, 9.99));
    }

    public function testItCombinesFiltersWithAnd(): void
    {
        $filter = Filter::all(
            Filter::equals('in_stock', true),
            Filter::greaterThan('price', 10),
        );

        self::assertSame('in_stock:=true && price:>10', (string) $filter);
    }

    public function testItCombinesFiltersWithOr(): void
    {
        $filter = Filter::any(
            Filter::equals('category', 'phones'),
            Filter::equals('category', 'tablets'),
        );

        self::assertSame('category:=`phones` || category:=`tablets`', (string) $filter);
    }

    public function testItNestsAllAndAnyFilters(): void
    {
        $filter = Filter::all(
            Filter::equals('in_stock', true),
            Filter::any(
                Filter::equals('category', 'phones'),
                Filter::equals('category', 'tablets'),
            ),
        );

        self::assertSame(
            'in_stock:=true && (category:=`phones` || category:=`tablets`)',
            (string) $filter,
        );
    }

    public function testItGroupsAllFiltersNestedInAny(): void
    {
        $filter = Filter::any(
            Filter::all(Filter::equals('in_stock', true), Filter::greaterThan('price', 10)),
            Filter::equals('featured', true),
        );

        self::assertSame('(in_stock:=true && price:>10) || featured:=true', (string) $filter);
    }

    public function testItKeepsSecurityFiltersBoundWhenCombinedWithAny(): void
    {
        $filter = Filter::all(
            Filter::equals('confidentiality', 'NORMAL'),
            Filter::any(Filter::equals('country', 'BE'), Filter::equals('country', 'RW')),
            Filter::any(Filter::equals('status', 'open'), Filter::equals('status', 'closed')),
        );

        self::assertSame(
            'confidentiality:=`NORMAL` && (country:=`BE` || country:=`RW`) && (status:=`open` || status:=`closed`)',
            (string) $filter,
        );
    }

    /**
     * @return iterable<string, array{\Closure(string): Filter}>
     */
    public static function quotedValueFilters(): iterable
    {
        yield 'equals' => [static fn (string $value): Filter => Filter::equals('country', $value)];
        yield 'notEquals' => [static fn (string $value): Filter => Filter::notEquals('country', $value)];
        yield 'greaterThan' => [static fn (string $value): Filter => Filter::greaterThan('country', $value)];
        yield 'in' => [static fn (string $value): Filter => Filter::in('country', ['BE', $value])];
        yield 'notIn' => [static fn (string $value): Filter => Filter::notIn('country', ['BE', $value])];
    }

    /**
     * @param \Closure(string): Filter $build
     */
    #[DataProvider('quotedValueFilters')]
    public function testItRejectsStringValuesContainingABacktick(\Closure $build): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $build('x`] || country:!=[`zzz');
    }
}
