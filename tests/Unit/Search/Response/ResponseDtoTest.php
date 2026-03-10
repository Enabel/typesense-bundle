<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Search\Response;

use Enabel\Typesense\Search\Response\FacetCount;
use Enabel\Typesense\Search\Response\FacetStats;
use Enabel\Typesense\Search\Response\FacetValue;
use Enabel\Typesense\Search\Response\GroupedHit;
use Enabel\Typesense\Search\Response\GroupedResponse;
use Enabel\Typesense\Search\Response\Hit;
use Enabel\Typesense\Search\Response\Response;
use PHPUnit\Framework\TestCase;

final class ResponseDtoTest extends TestCase
{
    public function testItHoldsFacetValueData(): void
    {
        $fv = new FacetValue('electronics', 42, '<mark>electronics</mark>', ['extra' => 1]);

        self::assertSame('electronics', $fv->value);
        self::assertSame(42, $fv->count);
        self::assertSame('<mark>electronics</mark>', $fv->highlighted);
        self::assertSame(['extra' => 1], $fv->raw);
    }

    public function testItHoldsFacetStatsData(): void
    {
        $fs = new FacetStats(100, 1.0, 999.99, 50000.0, 500.0);

        self::assertSame(100, $fs->totalValues);
        self::assertSame(1.0, $fs->min);
        self::assertSame(999.99, $fs->max);
        self::assertSame(50000.0, $fs->sum);
        self::assertSame(500.0, $fs->avg);
    }

    public function testItAllowsNullFacetStatsFields(): void
    {
        $fs = new FacetStats(0, null, null, null, null);

        self::assertNull($fs->min);
        self::assertNull($fs->max);
        self::assertNull($fs->sum);
        self::assertNull($fs->avg);
    }

    public function testItHoldsFacetCountData(): void
    {
        $fv = new FacetValue('phones', 10, 'phones');
        $stats = new FacetStats(5, 100.0, 2000.0, 5000.0, 1000.0);
        $fc = new FacetCount('category', [$fv], $stats, false);

        self::assertSame('category', $fc->fieldName);
        self::assertCount(1, $fc->counts);
        self::assertSame($fv, $fc->counts[0]);
        self::assertSame($stats, $fc->stats);
        self::assertFalse($fc->sampled);
    }

    public function testItAllowsNullFacetCountStats(): void
    {
        $fc = new FacetCount('brand', [], null, true);

        self::assertNull($fc->stats);
        self::assertTrue($fc->sampled);
    }

    public function testItHoldsHitData(): void
    {
        $doc = new \stdClass();
        $doc->id = 1;
        $hit = new Hit($doc, 123456789, ['highlights' => []]);

        self::assertSame($doc, $hit->document);
        self::assertSame(123456789, $hit->textMatch);
        self::assertSame(['highlights' => []], $hit->raw);
    }

    public function testItHoldsGroupedHitData(): void
    {
        $doc = new \stdClass();
        $hit = new Hit($doc, 100);
        $gh = new GroupedHit(['category' => 'phones'], [$hit], 5);

        self::assertSame(['category' => 'phones'], $gh->groupKey);
        self::assertCount(1, $gh->hits);
        self::assertSame(5, $gh->found);
    }

    public function testItHoldsResponseData(): void
    {
        $doc = new \stdClass();
        $doc->title = 'Widget';
        $hit = new Hit($doc, 100);

        $response = new Response(
            found: 50,
            outOf: 1000,
            page: 1,
            searchTimeMs: 5,
            searchCutoff: false,
            hits: [$hit],
            facetCounts: [],
            raw: ['request_params' => ['per_page' => 10]],
        );

        self::assertSame(50, $response->found);
        self::assertSame(1000, $response->outOf);
        self::assertSame(1, $response->page);
        self::assertSame(5, $response->searchTimeMs);
        self::assertFalse($response->searchCutoff);
        self::assertCount(1, $response->hits);
        self::assertSame([], $response->facetCounts);
    }

    public function testItExtractsDocumentsFromHits(): void
    {
        $doc1 = new \stdClass();
        $doc1->id = 1;
        $doc2 = new \stdClass();
        $doc2->id = 2;

        $response = new Response(
            found: 2,
            outOf: 100,
            page: 1,
            searchTimeMs: 1,
            searchCutoff: false,
            hits: [new Hit($doc1, 100), new Hit($doc2, 90)],
            facetCounts: [],
        );

        self::assertSame([$doc1, $doc2], $response->documents);
    }

    public function testItCalculatesTotalPagesFromFoundAndPerPage(): void
    {
        $response = new Response(
            found: 55,
            outOf: 1000,
            page: 1,
            searchTimeMs: 1,
            searchCutoff: false,
            hits: [],
            facetCounts: [],
            raw: ['request_params' => ['per_page' => 20]],
        );

        self::assertSame(3, $response->totalPages);
    }

    public function testItReturnsZeroTotalPagesWhenPerPageMissing(): void
    {
        $response = new Response(
            found: 55,
            outOf: 1000,
            page: 1,
            searchTimeMs: 1,
            searchCutoff: false,
            hits: [],
            facetCounts: [],
        );

        self::assertSame(0, $response->totalPages);
    }

    public function testItCalculatesTotalPagesForExactDivision(): void
    {
        $response = new Response(
            found: 40,
            outOf: 100,
            page: 1,
            searchTimeMs: 1,
            searchCutoff: false,
            hits: [],
            facetCounts: [],
            raw: ['request_params' => ['per_page' => 20]],
        );

        self::assertSame(2, $response->totalPages);
    }

    public function testItIncludesFacetCountsInResponse(): void
    {
        $fc = new FacetCount('category', [new FacetValue('phones', 10, 'phones')], null, false);

        $response = new Response(
            found: 10,
            outOf: 100,
            page: 1,
            searchTimeMs: 1,
            searchCutoff: false,
            hits: [],
            facetCounts: ['category' => $fc],
        );

        self::assertArrayHasKey('category', $response->facetCounts);
        self::assertSame($fc, $response->facetCounts['category']);
    }

    public function testItHoldsGroupedResponseData(): void
    {
        $doc = new \stdClass();
        $hit = new Hit($doc, 100);
        $gh = new GroupedHit(['category' => 'phones'], [$hit], 5);

        $response = new GroupedResponse(
            found: 100,
            foundDocs: 250,
            outOf: 1000,
            page: 1,
            searchTimeMs: 10,
            searchCutoff: false,
            groupedHits: [$gh],
            facetCounts: [],
        );

        self::assertSame(100, $response->found);
        self::assertSame(250, $response->foundDocs);
        self::assertSame(1000, $response->outOf);
        self::assertSame(1, $response->page);
        self::assertSame(10, $response->searchTimeMs);
        self::assertFalse($response->searchCutoff);
        self::assertCount(1, $response->groupedHits);
        self::assertSame([], $response->facetCounts);
    }

    public function testItDefaultsRawToEmptyArrayForAllDtos(): void
    {
        self::assertSame([], (new FacetValue('x', 1, 'x'))->raw);
        self::assertSame([], (new FacetStats(0, null, null, null, null))->raw);
        self::assertSame([], (new FacetCount('x', [], null, false))->raw);
        self::assertSame([], (new Hit(new \stdClass(), 0))->raw);
        self::assertSame([], (new GroupedHit([], [], 0))->raw);
        self::assertSame([], (new GroupedResponse(0, 0, 0, 0, 0, false, [], []))->raw);
        self::assertSame([], (new Response(0, 0, 0, 0, false, [], []))->raw);
    }
}
