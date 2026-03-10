<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Search;

use Enabel\Typesense\Search\Filter;
use Enabel\Typesense\Search\Query;
use Enabel\Typesense\Search\Sort;
use PHPUnit\Framework\TestCase;

final class QueryTest extends TestCase
{
    public function testItUsesWildcardWhenNoQueryGiven(): void
    {
        $params = Query::create()->toParams();

        self::assertSame('*', $params['q']);
    }

    public function testItUsesProvidedQueryString(): void
    {
        $params = Query::create('wireless headphones')->toParams();

        self::assertSame('wireless headphones', $params['q']);
    }

    public function testItSetsQueryByFields(): void
    {
        $params = Query::create('test')
            ->queryBy('name', 'description')
            ->toParams();

        self::assertSame('name,description', $params['query_by']);
    }

    public function testItAcceptsAFilterObject(): void
    {
        $params = Query::create()
            ->filterBy(Filter::equals('status', 'active'))
            ->toParams();

        self::assertSame('status:=`active`', $params['filter_by']);
    }

    public function testItAcceptsAFilterString(): void
    {
        $params = Query::create()
            ->filterBy('price:>100')
            ->toParams();

        self::assertSame('price:>100', $params['filter_by']);
    }

    public function testItResetsSortsOnSortBy(): void
    {
        $params = Query::create()
            ->sortBy('price', Sort::Desc)
            ->sortBy('name')
            ->toParams();

        self::assertSame('name:asc', $params['sort_by']);
    }

    public function testItAppendsOnThenSortBy(): void
    {
        $params = Query::create()
            ->sortBy('price')
            ->thenSortBy('rating', Sort::Desc)
            ->toParams();

        self::assertSame('price:asc,rating:desc', $params['sort_by']);
    }

    public function testItSetsFacetByFields(): void
    {
        $params = Query::create()
            ->facetBy('category', 'brand')
            ->toParams();

        self::assertSame('category,brand', $params['facet_by']);
    }

    public function testItSetsMaxFacetValues(): void
    {
        $params = Query::create()
            ->maxFacetValues(50)
            ->toParams();

        self::assertSame(50, $params['max_facet_values']);
    }

    public function testItSetsPageAndPerPage(): void
    {
        $params = Query::create()
            ->page(2)
            ->perPage(25)
            ->toParams();

        self::assertSame(2, $params['page']);
        self::assertSame(25, $params['per_page']);
    }

    public function testItOmitsUnsetValuesInParams(): void
    {
        $params = Query::create()->toParams();

        self::assertSame(['q' => '*'], $params);
    }

    public function testItBuildsAFullQueryWithAllOptions(): void
    {
        $params = Query::create('laptop')
            ->queryBy('name', 'description')
            ->filterBy(Filter::all(
                Filter::equals('in_stock', true),
                Filter::between('price', 100, 2000),
            ))
            ->sortBy('price')->thenSortBy('rating', Sort::Desc)
            ->facetBy('category', 'brand')
            ->maxFacetValues(10)
            ->page(1)
            ->perPage(20)
            ->toParams();

        self::assertSame('laptop', $params['q']);
        self::assertSame('name,description', $params['query_by']);
        self::assertSame('in_stock:=true && price:[100..2000]', $params['filter_by']);
        self::assertSame('price:asc,rating:desc', $params['sort_by']);
        self::assertSame('category,brand', $params['facet_by']);
        self::assertSame(10, $params['max_facet_values']);
        self::assertSame(1, $params['page']);
        self::assertSame(20, $params['per_page']);
    }

    public function testItReturnsSelfForFluentChaining(): void
    {
        $query = Query::create('test');

        self::assertSame($query, $query->queryBy('name'));
        self::assertSame($query, $query->filterBy('x:=1'));
        self::assertSame($query, $query->sortBy('name'));
        self::assertSame($query, $query->thenSortBy('price'));
        self::assertSame($query, $query->facetBy('cat'));
        self::assertSame($query, $query->maxFacetValues(10));
        self::assertSame($query, $query->page(1));
        self::assertSame($query, $query->perPage(10));
    }
}
