<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Functional;

use Enabel\Typesense\Search\Filter;
use Enabel\Typesense\Search\Query;
use Enabel\Typesense\Search\Sort;
use Enabel\Typesense\Tests\Fixtures\StringStatus;
use Enabel\Typesense\Tests\Fixtures\FunctionalProduct;

trait CollectionLifecycleTestTrait
{
    protected function getDocumentClasses(): array
    {
        return [FunctionalProduct::class];
    }

    public function testItCreatesAndDropsACollection(): void
    {
        $this->createCollection(FunctionalProduct::class);

        // Creating again should be idempotent
        $this->client->create(FunctionalProduct::class);

        $this->client->drop(FunctionalProduct::class);

        // Remove from tracked so tearDown doesn't try to drop again
        $this->createdCollections = [];

        // Verify drop succeeded by confirming we can recreate
        $this->createCollection(FunctionalProduct::class);
        self::assertTrue(true);
    }

    public function testItUpsertsAndFindsADocument(): void
    {
        $this->createCollection(FunctionalProduct::class);

        $product = $this->makeProduct(1, 'Wireless Mouse', 29.99);
        $collection = $this->client->collection(FunctionalProduct::class);
        $collection->upsert($product);

        $found = $collection->find('1');

        self::assertInstanceOf(FunctionalProduct::class, $found);
        self::assertSame(1, $found->id);
        self::assertSame('Wireless Mouse', $found->title);
        self::assertSame(29.99, $found->price);
        self::assertTrue($found->inStock);
        self::assertSame(['gadget', 'peripheral'], $found->tags);
        self::assertSame(50, $found->popularity);
        self::assertSame('A Wireless Mouse', $found->description);
        self::assertNull($found->subtitle);
        self::assertSame(StringStatus::Active, $found->status);
    }

    public function testItReturnsNullForMissingDocument(): void
    {
        $this->createCollection(FunctionalProduct::class);

        $result = $this->client->collection(FunctionalProduct::class)->find('999');

        self::assertNull($result);
    }

    public function testItUpdatesAnExistingDocumentOnUpsert(): void
    {
        $this->createCollection(FunctionalProduct::class);
        $collection = $this->client->collection(FunctionalProduct::class);

        $product = $this->makeProduct(1, 'Mouse', 19.99);
        $collection->upsert($product);

        $product->title = 'Updated Mouse';
        $product->price = 24.99;
        $collection->upsert($product);

        $found = $collection->find('1');
        self::assertSame('Updated Mouse', $found->title);
        self::assertSame(24.99, $found->price);
    }

    public function testItRemovesADocumentOnDelete(): void
    {
        $this->createCollection(FunctionalProduct::class);
        $collection = $this->client->collection(FunctionalProduct::class);

        $collection->upsert($this->makeProduct(1, 'To Delete', 10.0));
        $collection->delete('1');

        self::assertNull($collection->find('1'));
    }

    public function testItImportsDocumentsInBulk(): void
    {
        $this->createCollection(FunctionalProduct::class);
        $collection = $this->client->collection(FunctionalProduct::class);

        $products = [
            $this->makeProduct(1, 'Product A', 10.0),
            $this->makeProduct(2, 'Product B', 20.0),
            $this->makeProduct(3, 'Product C', 30.0),
        ];

        $collection->import($products);

        self::assertNotNull($collection->find('1'));
        self::assertNotNull($collection->find('2'));
        self::assertNotNull($collection->find('3'));
    }

    public function testItSearchesWithATextQuery(): void
    {
        $this->createCollection(FunctionalProduct::class);
        $collection = $this->client->collection(FunctionalProduct::class);

        $collection->import([
            $this->makeProduct(1, 'Wireless Mouse', 29.99, popularity: 100),
            $this->makeProduct(2, 'Wireless Keyboard', 49.99, popularity: 80),
            $this->makeProduct(3, 'Wired Mouse', 14.99, popularity: 60),
        ]);

        $response = $collection->search(
            Query::create('wireless')
                ->queryBy('title', 'description')
                ->perPage(10),
        );

        self::assertSame(2, $response->found);
        self::assertCount(2, $response->hits);
        self::assertCount(2, $response->documents);

        foreach ($response->documents as $doc) {
            self::assertInstanceOf(FunctionalProduct::class, $doc);
            self::assertStringContainsString('Wireless', $doc->title);
        }
    }

    public function testItSearchesWithFilters(): void
    {
        $this->createCollection(FunctionalProduct::class);
        $collection = $this->client->collection(FunctionalProduct::class);

        $collection->import([
            $this->makeProduct(1, 'Cheap Item', 5.0, popularity: 10),
            $this->makeProduct(2, 'Mid Item', 50.0, popularity: 50),
            $this->makeProduct(3, 'Expensive Item', 500.0, popularity: 90),
        ]);

        $response = $collection->search(
            Query::create()
                ->queryBy('title')
                ->filterBy(Filter::between('price', 10, 100)),
        );

        self::assertSame(1, $response->found);
        self::assertSame('Mid Item', $response->documents[0]->title);
    }

    public function testItSearchesWithSorting(): void
    {
        $this->createCollection(FunctionalProduct::class);
        $collection = $this->client->collection(FunctionalProduct::class);

        $collection->import([
            $this->makeProduct(1, 'A', 30.0, popularity: 10),
            $this->makeProduct(2, 'B', 10.0, popularity: 20),
            $this->makeProduct(3, 'C', 20.0, popularity: 30),
        ]);

        $response = $collection->search(
            Query::create()
                ->queryBy('title')
                ->sortBy('price', Sort::Asc),
        );

        self::assertSame(10.0, $response->documents[0]->price);
        self::assertSame(20.0, $response->documents[1]->price);
        self::assertSame(30.0, $response->documents[2]->price);
    }

    public function testItSearchesWithFacets(): void
    {
        $this->createCollection(FunctionalProduct::class);
        $collection = $this->client->collection(FunctionalProduct::class);

        $collection->import([
            $this->makeProduct(1, 'A', 10.0, tags: ['electronics', 'sale'], popularity: 10),
            $this->makeProduct(2, 'B', 20.0, tags: ['electronics'], popularity: 20),
            $this->makeProduct(3, 'C', 30.0, tags: ['sale'], popularity: 30),
        ]);

        $response = $collection->search(
            Query::create()
                ->queryBy('title')
                ->facetBy('tags'),
        );

        self::assertArrayHasKey('tags', $response->facetCounts);
        $tagFacet = $response->facetCounts['tags'];
        self::assertSame('tags', $tagFacet->fieldName);
        self::assertGreaterThanOrEqual(2, count($tagFacet->counts));
    }

    public function testItSearchesWithPagination(): void
    {
        $this->createCollection(FunctionalProduct::class);
        $collection = $this->client->collection(FunctionalProduct::class);

        $products = [];
        for ($i = 1; $i <= 5; $i++) {
            $products[] = $this->makeProduct($i, "Product {$i}", (float) $i, popularity: $i);
        }
        $collection->import($products);

        $page1 = $collection->search(
            Query::create()->queryBy('title')->perPage(2)->page(1)->sortBy('price'),
        );

        self::assertSame(5, $page1->found);
        self::assertCount(2, $page1->hits);
        self::assertSame(3, $page1->totalPages);

        $page2 = $collection->search(
            Query::create()->queryBy('title')->perPage(2)->page(2)->sortBy('price'),
        );

        self::assertCount(2, $page2->hits);
        self::assertNotSame(
            $page1->documents[0]->id,
            $page2->documents[0]->id,
        );
    }

    public function testItSearchesWithGrouping(): void
    {
        $this->createCollection(FunctionalProduct::class);
        $collection = $this->client->collection(FunctionalProduct::class);

        $collection->import([
            $this->makeProduct(1, 'A Active', 10.0, status: StringStatus::Active, popularity: 10),
            $this->makeProduct(2, 'B Active', 20.0, status: StringStatus::Active, popularity: 20),
            $this->makeProduct(3, 'C Inactive', 30.0, status: StringStatus::Inactive, popularity: 30),
        ]);

        $response = $this->client->collection(FunctionalProduct::class)->searchGrouped(
            Query::create()->queryBy('title'),
            groupBy: 'status',
            groupLimit: 2,
        );

        self::assertGreaterThanOrEqual(2, count($response->groupedHits));

        foreach ($response->groupedHits as $group) {
            self::assertNotEmpty($group->groupKey);
            self::assertNotEmpty($group->hits);
            foreach ($group->hits as $hit) {
                self::assertInstanceOf(FunctionalProduct::class, $hit->document);
            }
        }
    }

    public function testItReturnsRawSearchResults(): void
    {
        $this->createCollection(FunctionalProduct::class);
        $collection = $this->client->collection(FunctionalProduct::class);

        $collection->upsert($this->makeProduct(1, 'Raw Test', 10.0));

        $result = $collection->searchRaw([
            'q' => '*',
            'query_by' => 'title',
        ]);

        self::assertArrayHasKey('found', $result);
        self::assertArrayHasKey('hits', $result);
        self::assertSame(1, $result['found']);
    }

    protected function makeProduct(
        int $id,
        string $title,
        float $price,
        bool $inStock = true,
        array $tags = ['gadget', 'peripheral'],
        int $popularity = 50,
        ?string $description = null,
        ?string $subtitle = null,
        StringStatus $status = StringStatus::Active,
    ): FunctionalProduct {
        $product = new FunctionalProduct();
        $product->id = $id;
        $product->title = $title;
        $product->price = $price;
        $product->inStock = $inStock;
        $product->tags = $tags;
        $product->popularity = $popularity;
        $product->description = $description ?? "A {$title}";
        $product->subtitle = $subtitle;
        $product->createdAt = new \DateTimeImmutable('2025-01-15');
        $product->status = $status;

        return $product;
    }
}
