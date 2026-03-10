<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit;

use Enabel\Typesense\Collection;
use Enabel\Typesense\Document\DenormalizerInterface;
use Enabel\Typesense\Document\DocumentNormalizerInterface;
use Enabel\Typesense\Exception\ImportException;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Search\Filter;
use Enabel\Typesense\Search\Query;
use Enabel\Typesense\Search\Response\Response;
use Enabel\Typesense\Search\Response\GroupedResponse;
use Enabel\Typesense\Search\Sort;
use Enabel\Typesense\Type\IntType;
use PHPUnit\Framework\TestCase;
use Typesense\Documents;
use Typesense\Exceptions\ObjectNotFound;

final class CollectionTest extends TestCase
{
    private Collection $collection;
    private DocumentMetadata $metadata;
    private \Typesense\Collection&\PHPUnit\Framework\MockObject\MockObject $typesenseCollection;
    private DenormalizerInterface&\PHPUnit\Framework\MockObject\MockObject $denormalizer;
    private DocumentNormalizerInterface&\PHPUnit\Framework\MockObject\MockObject $normalizer;
    private Documents&\PHPUnit\Framework\MockObject\MockObject $documents;

    protected function setUp(): void
    {
        $this->metadata = new DocumentMetadata(
            className: 'App\Entity\Product',
            collection: 'products',
            defaultSortingField: null,
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [],
        );

        $this->typesenseCollection = $this->createMock(\Typesense\Collection::class);
        $this->documents = $this->createMock(Documents::class);
        $this->typesenseCollection->method('getDocuments')->willReturn($this->documents);
        $this->denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->normalizer = $this->createMock(DocumentNormalizerInterface::class);

        $this->collection = new Collection(
            $this->metadata,
            $this->typesenseCollection,
            $this->denormalizer,
            $this->normalizer,
        );
    }

    public function testItReturnsAResponseOnSearch(): void
    {
        $product = new \stdClass();
        $product->id = 1;

        $this->documents->method('search')->willReturn([
            'found' => 1,
            'out_of' => 100,
            'page' => 1,
            'search_time_ms' => 5,
            'search_cutoff' => false,
            'hits' => [
                ['document' => ['id' => '1', 'title' => 'Widget'], 'text_match' => 123456],
            ],
            'facet_counts' => [],
            'request_params' => ['per_page' => 10],
        ]);

        $this->denormalizer->method('denormalize')
            ->with([['id' => '1', 'title' => 'Widget']], 'App\Entity\Product')
            ->willReturn([$product]);

        $response = $this->collection->search(
            Query::create('widget')->queryBy('title'),
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(1, $response->found);
        self::assertSame(100, $response->outOf);
        self::assertCount(1, $response->hits);
        self::assertSame($product, $response->hits[0]->document);
        self::assertSame(123456, $response->hits[0]->textMatch);
    }

    public function testItIncludesFacetCountsInSearchResponse(): void
    {
        $this->documents->method('search')->willReturn([
            'found' => 0,
            'out_of' => 100,
            'page' => 1,
            'search_time_ms' => 1,
            'search_cutoff' => false,
            'hits' => [],
            'facet_counts' => [
                [
                    'field_name' => 'category',
                    'counts' => [
                        ['value' => 'phones', 'count' => 10, 'highlighted' => '<mark>phones</mark>'],
                    ],
                    'stats' => [
                        'total_values' => 5,
                        'min' => 1.0,
                        'max' => 10.0,
                        'sum' => 30.0,
                        'avg' => 6.0,
                    ],
                    'sampled' => false,
                ],
            ],
            'request_params' => ['per_page' => 10],
        ]);

        $this->denormalizer->method('denormalize')->willReturn([]);

        $response = $this->collection->search(Query::create());

        self::assertArrayHasKey('category', $response->facetCounts);
        $facet = $response->facetCounts['category'];
        self::assertSame('category', $facet->fieldName);
        self::assertCount(1, $facet->counts);
        self::assertSame('phones', $facet->counts[0]->value);
        self::assertSame(10, $facet->counts[0]->count);
        self::assertNotNull($facet->stats);
        self::assertSame(5, $facet->stats->totalValues);
        self::assertFalse($facet->sampled);
    }

    public function testItReturnsAGroupedResponseOnGroupedSearch(): void
    {
        $product = new \stdClass();
        $product->id = 1;

        $this->documents->method('search')->willReturn([
            'found' => 10,
            'found_docs' => 25,
            'out_of' => 100,
            'page' => 1,
            'search_time_ms' => 5,
            'search_cutoff' => false,
            'grouped_hits' => [
                [
                    'group_key' => ['category' => 'phones'],
                    'hits' => [
                        ['document' => ['id' => '1', 'title' => 'Phone'], 'text_match' => 100],
                    ],
                    'found' => 5,
                ],
            ],
            'facet_counts' => [],
        ]);

        $this->denormalizer->method('denormalize')
            ->willReturn([$product]);

        $response = $this->collection->searchGrouped(
            Query::create('phone')->queryBy('title'),
            groupBy: 'category',
            groupLimit: 3,
        );

        self::assertInstanceOf(GroupedResponse::class, $response);
        self::assertSame(10, $response->found);
        self::assertSame(25, $response->foundDocs);
        self::assertCount(1, $response->groupedHits);
        self::assertSame(['category' => 'phones'], $response->groupedHits[0]->groupKey);
        self::assertCount(1, $response->groupedHits[0]->hits);
        self::assertSame($product, $response->groupedHits[0]->hits[0]->document);
        self::assertSame(5, $response->groupedHits[0]->found);
    }

    public function testItReturnsARawArrayOnRawSearch(): void
    {
        $rawResult = ['found' => 1, 'hits' => [['document' => ['id' => '1']]]];

        $this->documents->method('search')
            ->with(['q' => '*', 'query_by' => 'name'])
            ->willReturn($rawResult);

        $result = $this->collection->searchRaw(['q' => '*', 'query_by' => 'name']);

        self::assertSame($rawResult, $result);
    }

    public function testItReturnsAnObjectOnFind(): void
    {
        $product = new \stdClass();
        $product->id = 42;

        $document = $this->createMock(\Typesense\Document::class);
        $document->method('retrieve')->willReturn(['id' => '42', 'title' => 'Widget']);

        $this->documents->method('offsetGet')->with('42')->willReturn($document);

        $this->denormalizer->method('denormalize')
            ->with([['id' => '42', 'title' => 'Widget']], 'App\Entity\Product')
            ->willReturn([$product]);

        $result = $this->collection->find('42');

        self::assertSame($product, $result);
    }

    public function testItReturnsNullWhenDocumentNotFound(): void
    {
        $document = $this->createMock(\Typesense\Document::class);
        $document->method('retrieve')->willThrowException(new ObjectNotFound());

        $this->documents->method('offsetGet')->with('99')->willReturn($document);

        $result = $this->collection->find('99');

        self::assertNull($result);
    }

    public function testItCallsSdkOnDelete(): void
    {
        $document = $this->createMock(\Typesense\Document::class);
        $document->expects(self::once())->method('delete')->willReturn([]);

        $this->documents->method('offsetGet')->with('42')->willReturn($document);

        $this->collection->delete('42');
    }

    public function testItNormalizesAndCallsSdkOnUpsert(): void
    {
        $product = new \stdClass();
        $product->id = 1;

        $this->normalizer->method('normalize')
            ->with([$product])
            ->willReturn([['id' => '1', 'title' => 'Widget']]);

        $this->documents->expects(self::once())
            ->method('upsert')
            ->with(['id' => '1', 'title' => 'Widget'])
            ->willReturn([]);

        $this->collection->upsert($product);
    }

    public function testItNormalizesAndCallsSdkOnImport(): void
    {
        $product1 = new \stdClass();
        $product2 = new \stdClass();

        $this->normalizer->method('normalize')
            ->with([$product1, $product2])
            ->willReturn([
                ['id' => '1', 'title' => 'One'],
                ['id' => '2', 'title' => 'Two'],
            ]);

        $this->documents->expects(self::once())
            ->method('import')
            ->with(
                [['id' => '1', 'title' => 'One'], ['id' => '2', 'title' => 'Two']],
                ['action' => 'upsert'],
            )
            ->willReturn([
                ['success' => true],
                ['success' => true],
            ]);

        $this->collection->import([$product1, $product2]);
    }

    public function testItThrowsOnImportFailure(): void
    {
        $product = new \stdClass();

        $this->normalizer->method('normalize')
            ->willReturn([['id' => '1', 'title' => 'Bad']]);

        $this->documents->method('import')
            ->willReturn([
                ['success' => false, 'error' => 'Bad data', 'document' => '{"id":"1"}'],
            ]);

        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('1 document(s) failed to import');

        $this->collection->import([$product]);
    }
}
