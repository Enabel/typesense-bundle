<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit;

use Enabel\Typesense\Client;
use Enabel\Typesense\Collection;
use Enabel\Typesense\Document\DenormalizerInterface;
use Enabel\Typesense\Document\DocumentNormalizerInterface;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Enabel\Typesense\Schema\SchemaBuilderInterface;
use Enabel\Typesense\Type\IntType;
use PHPUnit\Framework\TestCase;
use Typesense\Collections;

final class ClientTest extends TestCase
{
    private Client $client;
    private \Typesense\Client&\PHPUnit\Framework\MockObject\MockObject $typesenseClient;
    private MetadataRegistryInterface&\PHPUnit\Framework\MockObject\MockObject $registry;
    private DocumentNormalizerInterface&\PHPUnit\Framework\MockObject\MockObject $normalizer;
    private SchemaBuilderInterface&\PHPUnit\Framework\MockObject\MockObject $schemaBuilder;
    private DenormalizerInterface&\PHPUnit\Framework\MockObject\MockObject $denormalizer;
    private DocumentMetadata $metadata;
    private Collections&\PHPUnit\Framework\MockObject\MockObject $collections;

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

        $this->typesenseClient = $this->createMock(\Typesense\Client::class);
        $this->collections = $this->createMock(Collections::class);
        $this->typesenseClient->method('getCollections')->willReturn($this->collections);
        $this->registry = $this->createMock(MetadataRegistryInterface::class);
        $this->registry->method('get')->willReturn($this->metadata);
        $this->normalizer = $this->createMock(DocumentNormalizerInterface::class);
        $this->schemaBuilder = $this->createMock(SchemaBuilderInterface::class);
        $this->denormalizer = $this->createMock(DenormalizerInterface::class);

        $this->client = new Client(
            $this->typesenseClient,
            $this->registry,
            $this->normalizer,
            $this->schemaBuilder,
            ['App\Entity\Product' => $this->denormalizer],
        );
    }

    public function testItReturnsACollectionInstance(): void
    {
        $tsCollection = $this->createMock(\Typesense\Collection::class);
        $this->collections->method('offsetGet')->with('products')->willReturn($tsCollection);

        $collection = $this->client->collection('App\Entity\Product');

        self::assertInstanceOf(Collection::class, $collection);
    }

    public function testItThrowsWhenDenormalizerIsNotRegistered(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No denormalizer registered for "App\Entity\Unknown"');

        $unknownMetadata = new DocumentMetadata(
            className: 'App\Entity\Unknown',
            collection: 'unknown',
            defaultSortingField: null,
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [],
        );
        $this->registry = $this->createMock(MetadataRegistryInterface::class);
        $this->registry->method('get')->willReturn($unknownMetadata);

        $client = new Client(
            $this->typesenseClient,
            $this->registry,
            $this->normalizer,
            $this->schemaBuilder,
            [],
        );

        $client->collection('App\Entity\Unknown');
    }

    public function testItBuildsSchemaAndCallsSdkOnCreate(): void
    {
        $schema = ['name' => 'products', 'fields' => []];
        $this->schemaBuilder->method('build')->with($this->metadata)->willReturn($schema);

        $tsCollection = $this->createMock(\Typesense\Collection::class);
        $tsCollection->method('exists')->willReturn(false);
        $this->collections->method('offsetGet')->with('products')->willReturn($tsCollection);

        $this->collections->expects(self::once())
            ->method('create')
            ->with($schema)
            ->willReturn([]);

        $this->client->create('App\Entity\Product');
    }

    public function testItSkipsCreateIfCollectionAlreadyExists(): void
    {
        $schema = ['name' => 'products', 'fields' => []];
        $this->schemaBuilder->method('build')->willReturn($schema);

        $tsCollection = $this->createMock(\Typesense\Collection::class);
        $tsCollection->method('exists')->willReturn(true);
        $this->collections->method('offsetGet')->with('products')->willReturn($tsCollection);

        $this->collections->expects(self::never())->method('create');

        $this->client->create('App\Entity\Product');
    }

    public function testItDeletesTheCollectionOnDrop(): void
    {
        $tsCollection = $this->createMock(\Typesense\Collection::class);
        $tsCollection->expects(self::once())->method('delete')->willReturn([]);
        $this->collections->method('offsetGet')->with('products')->willReturn($tsCollection);

        $this->client->drop('App\Entity\Product');
    }
}
