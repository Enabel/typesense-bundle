# enabel/typesense

A PHP library for [Typesense](https://typesense.org) with attribute-based document mapping, a fluent search API, and optional Doctrine/Symfony integrations.

## Installation

```bash
composer require enabel/typesense
```

## Quick Start

### 1. Define a Document

Map your PHP class to a Typesense collection using attributes:

```php
use Enabel\Typesense\Mapping as Typesense;
use Enabel\Typesense\Type\StringType;

#[Typesense\Document(collection: 'products', defaultSortingField: 'popularity')]
class Product
{
    #[Typesense\Id]
    public int $id;

    #[Typesense\Field(facet: true, infix: true)]
    public string $title;

    #[Typesense\Field(sort: true)]
    public float $price;

    #[Typesense\Field]
    public bool $inStock;

    #[Typesense\Field(type: new StringType(array: true), facet: true)]
    public array $tags;

    #[Typesense\Field(sort: true, index: false)]
    public int $popularity;

    #[Typesense\Field]
    public \DateTimeImmutable $createdAt;

    #[Typesense\Field(optional: true)]
    public ?string $description;
}
```

### 2. Search

```php
use Enabel\Typesense\Search\{Query, Filter, Sort};

$response = $collection->search(
    Query::create('wireless headphones')
        ->queryBy('title', 'description')
        ->filterBy(Filter::all(
            Filter::equals('inStock', true),
            Filter::between('price', 10, 500),
        ))
        ->sortBy('price', Sort::Asc)
        ->facetBy('tags')
        ->perPage(20)
);

foreach ($response->documents as $product) {
    echo "{$product->title} - \${$product->price}\n";
}

echo "Found {$response->found} results across {$response->totalPages} pages\n";
```

---

## Symfony Bundle

### Installation

```bash
composer require enabel/typesense symfony/framework-bundle symfony/console
```

Register the bundle (if not using Symfony Flex):

```php
// config/bundles.php
return [
    // ...
    Enabel\Typesense\Bundle\EnabelTypesenseBundle::class => ['all' => true],
];
```

### Configuration

```yaml
# config/packages/enabel_typesense.yaml
enabel_typesense:
    client:
        url: '%env(TYPESENSE_URL)%'           # e.g. http://localhost:8108
        api_key: '%env(TYPESENSE_API_KEY)%'

    default_denormalizer: ~     # optional: service ID for default denormalizer
    default_data_provider: ~   # optional: service ID for default data provider

    collections:
        App\Entity\Product: ~                   # uses defaults
        App\Entity\Article:
            denormalizer: app.article_denormalizer   # override per collection
            data_provider: app.article_provider      # override per collection
```

### Registered Services

The bundle automatically registers:

| Service | Description |
|---------|-------------|
| `Enabel\Typesense\ClientInterface` | Main client |
| `Enabel\Typesense\Metadata\MetadataRegistryInterface` | Cached metadata registry |
| `Enabel\Typesense\Document\DocumentNormalizerInterface` | Document normalizer |
| `Enabel\Typesense\Schema\SchemaBuilderInterface` | Schema builder |
| `Enabel\Typesense\Doctrine\IndexListener` | Auto-registered if Doctrine is present |

### Console Commands

```bash
# Create all configured collections
php bin/console enabel:typesense:create

# Create a specific collection
php bin/console enabel:typesense:create --class='App\Entity\Product'

# Drop collections (requires --force)
php bin/console enabel:typesense:drop --force
php bin/console enabel:typesense:drop --class='App\Entity\Product' --force

# Import documents (batched in chunks of 100)
php bin/console enabel:typesense:import
php bin/console enabel:typesense:import --class='App\Entity\Product'

# Search a collection
php bin/console enabel:typesense:search 'App\Entity\Product' \
    --query='headphones' \
    --query-by='title,description' \
    --filter='price:<500' \
    --per-page=10
```

### Usage in Services

```php
use Enabel\Typesense\ClientInterface;
use Enabel\Typesense\Search\{Query, Filter, Sort};

class ProductSearchService
{
    public function __construct(
        private ClientInterface $client,
    ) {}

    public function search(string $term, int $page = 1): array
    {
        $response = $this->client->collection(Product::class)->search(
            Query::create($term)
                ->queryBy('title', 'description')
                ->filterBy(Filter::equals('inStock', true))
                ->sortBy('popularity', Sort::Desc)
                ->page($page)
                ->perPage(20)
        );

        return $response->documents;
    }
}
```

### Doctrine Integration

When Doctrine ORM is available, the bundle automatically registers an `IndexListener` that syncs entities to Typesense on persist, update, and remove. The listener treats the database as the source of truth — if a Typesense sync fails, it logs a warning rather than throwing an exception.

To return fully hydrated Doctrine entities from search results, configure a `DoctrineDenormalizer` as your denormalizer:

```yaml
enabel_typesense:
    default_denormalizer: Enabel\Typesense\Doctrine\DoctrineDenormalizer
    default_data_provider: Enabel\Typesense\Doctrine\DoctrineDataProvider

    collections:
        App\Entity\Product: ~
```

---

## Standalone Usage (without Symfony)

### Set Up the Client

```php
use Enabel\Typesense\Client;
use Enabel\Typesense\Document\{DocumentNormalizer, ObjectDenormalizer};
use Enabel\Typesense\Metadata\{MetadataReader, MetadataRegistry};
use Enabel\Typesense\Schema\SchemaBuilder;

$typesense = new \Typesense\Client([
    'api_key' => 'your-api-key',
    'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
]);

$registry   = new MetadataRegistry(new MetadataReader());
$normalizer = new DocumentNormalizer($registry);
$denormalizer = new ObjectDenormalizer($registry);

$client = new Client(
    typesenseClient: $typesense,
    registry: $registry,
    normalizer: $normalizer,
    schemaBuilder: new SchemaBuilder(),
    denormalizers: [
        Product::class => $denormalizer,
    ],
);
```

### Create Collection & Index Documents

```php
$client->create(Product::class);

$collection = $client->collection(Product::class);
$collection->upsert($product);
$collection->import([$product1, $product2, $product3]);
```

### Doctrine Integration (Manual Wiring)

For projects using Doctrine ORM without Symfony, wire the integrations manually:

#### DoctrineDenormalizer

Fetches real entities from the database instead of creating plain objects:

```php
use Enabel\Typesense\Doctrine\DoctrineDenormalizer;

$denormalizer = new DoctrineDenormalizer($entityManager, $registry);

$client = new Client(
    typesenseClient: $typesense,
    registry: $registry,
    normalizer: $normalizer,
    schemaBuilder: new SchemaBuilder(),
    denormalizers: [
        Product::class => $denormalizer,
    ],
);

// search() now returns fully hydrated Doctrine entities
$response = $client->collection(Product::class)->search($query);
$entity = $response->documents[0]; // Doctrine-managed Product entity
```

#### DoctrineDataProvider

Streams entities for bulk import with low memory usage:

```php
use Enabel\Typesense\Doctrine\DoctrineDataProvider;

$provider = new DoctrineDataProvider($entityManager);

foreach ($provider->provide(Product::class) as $product) {
    // Each entity is detached after yielding
}
```

#### IndexListener

Automatically syncs Doctrine entities to Typesense on persist, update, and remove:

```php
use Enabel\Typesense\Doctrine\IndexListener;

$listener = new IndexListener(
    client: $client,
    classNames: [Product::class],
    logger: $logger,           // optional, logs sync failures as warnings
    registry: $registry,       // optional, used for ID extraction on preRemove
);

// Register as Doctrine event listener
$entityManager->getEventManager()->addEventListener(
    [Events::postPersist, Events::postUpdate, Events::preRemove],
    $listener,
);
```

---

## Mapping Attributes

Import the mapping namespace as `Typesense` for concise, readable attribute declarations:

```php
use Enabel\Typesense\Mapping as Typesense;
```

### `#[Typesense\Document]`

Applied to a class. Defines the Typesense collection name.

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `collection` | `string` | *(required)* | Typesense collection name |
| `defaultSortingField` | `?string` | `null` | Default sort field (must have `index: true`) |

### `#[Typesense\Id]`

Applied to exactly one property. Marks it as the document ID.

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `type` | `?TypeInterface` | `null` | Explicit type (auto-inferred if omitted) |

### `#[Typesense\Field]`

Applied to properties that should be indexed in Typesense.

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `type` | `?TypeInterface` | `null` | Explicit type (required for `array` properties) |
| `facet` | `bool` | `false` | Enable faceting |
| `sort` | `bool` | `false` | Enable sorting |
| `index` | `bool` | `true` | Index for searching/filtering |
| `store` | `bool` | `true` | Persist on disk |
| `infix` | `bool` | `false` | Enable infix (substring) searching |
| `optional` | `bool` | `false` | Allow absent values (auto-set for nullable properties) |

## Type System

Types are inferred from PHP property types when possible. Use explicit types for arrays or custom conversions.

| PHP Type | Typesense Type | Inferred |
|----------|---------------|----------|
| `string` | `string` | Yes |
| `int` | `int64` | Yes |
| `float` | `float` | Yes |
| `bool` | `bool` | Yes |
| `DateTimeImmutable` / `DateTime` | `int64` (Unix timestamp) | Yes |
| Backed enum | `string` or `int32` | Yes |
| `array` | *(varies)* | No, requires explicit `type` |

### Available Types

```php
use Enabel\Typesense\Type\{StringType, IntType, FloatType, BoolType, DateTimeType, BackedEnumType};

new StringType()                          // string
new StringType(array: true)               // string[]
new IntType()                             // int64
new IntType(int32: true)                  // int32
new IntType(array: true)                  // int64[]
new FloatType()                           // float
new BoolType()                            // bool
new DateTimeType()                        // int64 (Unix timestamp)
new BackedEnumType(Status::class)         // string or int32
new BackedEnumType(Status::class, array: true) // string[] or int32[]
```

### Casting Helpers

When building filters with datetime or enum values, use the static `cast` methods:

```php
use Enabel\Typesense\Type\{DateTimeType, BackedEnumType};

Filter::greaterThan('createdAt', DateTimeType::cast(new \DateTimeImmutable('-7 days')));
Filter::equals('status', BackedEnumType::cast(Status::Active));
```

## Search API

### Query Builder

```php
use Enabel\Typesense\Search\{Query, Sort};

$query = Query::create('search term')      // null or '*' for wildcard
    ->queryBy('title', 'description')      // Fields to search in
    ->filterBy($filter)                    // Filter or string expression
    ->sortBy('price', Sort::Asc)           // Primary sort (resets previous sorts)
    ->thenSortBy('rating', Sort::Desc)     // Additional sort
    ->facetBy('category', 'brand')         // Facet fields
    ->maxFacetValues(20)                   // Max facet values returned
    ->page(1)                              // Page number
    ->perPage(25);                         // Results per page
```

### Filter Builder

```php
use Enabel\Typesense\Search\Filter;

// Comparison
Filter::equals('status', 'active');
Filter::notEquals('status', 'draft');
Filter::greaterThan('price', 100);
Filter::greaterThanOrEqual('price', 100);
Filter::lessThan('price', 500);
Filter::lessThanOrEqual('price', 500);
Filter::between('price', 100, 500);

// String matching
Filter::matches('title', 'wire*');

// Set operations
Filter::in('category', ['electronics', 'computers']);
Filter::notIn('status', ['archived', 'deleted']);

// Logical operators
Filter::all($filter1, $filter2);  // AND
Filter::any($filter1, $filter2);  // OR
```

### Response Objects

#### `Response` (standard search)

| Property | Type | Description |
|----------|------|-------------|
| `found` | `int` | Total matching documents |
| `outOf` | `int` | Total documents in collection |
| `page` | `int` | Current page |
| `searchTimeMs` | `int` | Search duration in ms |
| `searchCutoff` | `bool` | Whether search was cut off |
| `hits` | `Hit[]` | Search hits |
| `documents` | `object[]` | Denormalized PHP objects (computed) |
| `totalPages` | `int` | Total pages (computed) |
| `facetCounts` | `array<string, FacetCount>` | Facet results keyed by field |
| `raw` | `array` | Full raw Typesense response |

#### `Hit`

| Property | Type | Description |
|----------|------|-------------|
| `document` | `object` | Denormalized PHP object |
| `textMatch` | `int` | Match score |
| `raw` | `array` | Raw hit data |

#### `GroupedResponse` (grouped search)

```php
$response = $collection->searchGrouped(
    Query::create('laptop')->queryBy('title'),
    groupBy: 'category',   // Field must have facet: true
    groupLimit: 5,
);

foreach ($response->groupedHits as $group) {
    echo "Group: " . implode(', ', $group->groupKey) . "\n";
    foreach ($group->hits as $hit) {
        echo "  - {$hit->document->title}\n";
    }
}
```

### Raw Search

Bypass the query builder and response DTOs entirely:

```php
$raw = $collection->searchRaw([
    'q' => '*',
    'query_by' => 'title',
    'per_page' => 10,
]);
```

## Collection Operations

```php
$collection = $client->collection(Product::class);

// CRUD
$collection->upsert($product);
$collection->find('42');         // Returns Product or null
$collection->delete('42');

// Bulk import (throws ImportException on partial failure)
$collection->import([$p1, $p2, $p3]);

// Collection lifecycle
$client->create(Product::class);  // Idempotent
$client->drop(Product::class);
```

## License

MIT
