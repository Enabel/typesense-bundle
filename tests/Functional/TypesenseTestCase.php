<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Functional;

use Enabel\Typesense\Client;
use Enabel\Typesense\Document\DocumentNormalizer;
use Enabel\Typesense\Document\ObjectDenormalizer;
use Enabel\Typesense\Metadata\MetadataReader;
use Enabel\Typesense\Metadata\MetadataRegistry;
use Enabel\Typesense\Schema\SchemaBuilder;
use PHPUnit\Framework\TestCase;

abstract class TypesenseTestCase extends TestCase
{
    protected Client $client;

    /** @var class-string[] */
    protected array $createdCollections = [];

    abstract protected function getTypesenseUrl(): string;

    protected function setUp(): void
    {
        $url = $this->getTypesenseUrl();
        $apiKey = $_ENV['TYPESENSE_API_KEY'] ?? '123';

        $parsed = parse_url($url);
        assert(is_array($parsed));

        $typesenseClient = new \Typesense\Client([
            'api_key' => $apiKey,
            'nodes' => [
                [
                    'host' => $parsed['host'],
                    'port' => (string) ($parsed['port'] ?? 8108),
                    'protocol' => $parsed['scheme'] ?? 'http',
                ],
            ],
        ]);

        $reader = new MetadataReader();
        $registry = new MetadataRegistry($reader);
        $normalizer = new DocumentNormalizer($registry);
        $denormalizer = new ObjectDenormalizer($registry);
        $schemaBuilder = new SchemaBuilder();

        $this->client = new Client(
            $typesenseClient,
            $registry,
            $normalizer,
            $schemaBuilder,
            $this->buildDenormalizerMap($denormalizer),
        );
    }

    protected function tearDown(): void
    {
        foreach ($this->createdCollections as $className) {
            try {
                $this->client->drop($className);
            } catch (\Throwable) {
            }
        }
    }

    /**
     * @param class-string $className
     */
    protected function createCollection(string $className): void
    {
        // Drop leftover from previous failed runs
        try {
            $this->client->drop($className);
        } catch (\Throwable) {
        }

        $this->createdCollections[] = $className;

        // Recreate client to reset SDK's exists() cache after drop
        $this->setUp();
        $this->client->create($className);
    }

    /**
     * @return array<class-string, ObjectDenormalizer>
     */
    private function buildDenormalizerMap(ObjectDenormalizer $denormalizer): array
    {
        return array_fill_keys($this->getDocumentClasses(), $denormalizer);
    }

    /**
     * @return class-string[]
     */
    abstract protected function getDocumentClasses(): array;
}
