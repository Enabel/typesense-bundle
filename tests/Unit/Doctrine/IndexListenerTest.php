<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Enabel\Typesense\ClientInterface;
use Enabel\Typesense\CollectionInterface;
use Enabel\Typesense\Doctrine\IndexListener;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Enabel\Typesense\Type\IntType;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Typesense\Exceptions\TypesenseClientError;

final class IndexListenerTest extends TestCase
{
    private ClientInterface $client;
    private LoggerInterface $logger;
    private IndexListener $listener;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new IndexListener(
            $this->client,
            [\stdClass::class],
            $this->logger,
        );
    }

    public function testItUpsertsDocumentOnPostPersist(): void
    {
        $entity = new \stdClass();
        $collection = $this->createMock(CollectionInterface::class);

        $this->client->method('collection')
            ->with(\stdClass::class)
            ->willReturn($collection);

        $collection->expects(self::once())
            ->method('upsert')
            ->with($entity);

        $event = $this->createEvent(PostPersistEventArgs::class, $entity);
        $this->listener->postPersist($event);
    }

    public function testItUpsertsDocumentOnPostUpdate(): void
    {
        $entity = new \stdClass();
        $collection = $this->createMock(CollectionInterface::class);

        $this->client->method('collection')
            ->with(\stdClass::class)
            ->willReturn($collection);

        $collection->expects(self::once())
            ->method('upsert')
            ->with($entity);

        $event = $this->createEvent(PostUpdateEventArgs::class, $entity);
        $this->listener->postUpdate($event);
    }

    public function testItDeletesDocumentOnPreRemove(): void
    {
        $entity = new class {
            public int $id = 42;
        };

        $metadata = new DocumentMetadata(
            collection: 'products',
            className: $entity::class,
            idProperty: 'id',
            idType: new IntType(),
            fields: [],
        );

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $registry->method('get')->willReturn($metadata);

        $collection = $this->createMock(CollectionInterface::class);

        $this->client->method('collection')
            ->with($entity::class)
            ->willReturn($collection);

        $collection->expects(self::once())
            ->method('delete')
            ->with('42');

        $listener = new IndexListener(
            $this->client,
            [$entity::class],
            $this->logger,
            $registry,
        );

        $event = $this->createEvent(PreRemoveEventArgs::class, $entity);
        $listener->preRemove($event);
    }

    public function testItSkipsUnregisteredEntity(): void
    {
        $listener = new IndexListener(
            $this->client,
            ['App\Entity\Unknown'],
            $this->logger,
        );

        $entity = new \stdClass();

        $this->client->expects(self::never())->method('collection');

        $event = $this->createEvent(PostPersistEventArgs::class, $entity);
        $listener->postPersist($event);
    }

    public function testItCatchesTypesenseErrorAndLogsWarning(): void
    {
        $entity = new \stdClass();
        $collection = $this->createMock(CollectionInterface::class);

        $this->client->method('collection')->willReturn($collection);
        $collection->method('upsert')->willThrowException(
            new TypesenseClientError('Connection refused'),
        );

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(self::stringContains('Connection refused'));

        $event = $this->createEvent(PostPersistEventArgs::class, $entity);
        $this->listener->postPersist($event);
    }

    public function testItHandlesTypesenseErrorWithoutLogger(): void
    {
        $listener = new IndexListener(
            $this->client,
            [\stdClass::class],
        );

        $entity = new \stdClass();
        $collection = $this->createMock(CollectionInterface::class);

        $this->client->method('collection')->willReturn($collection);
        $collection->method('upsert')->willThrowException(
            new TypesenseClientError('Connection refused'),
        );

        $event = $this->createEvent(PostPersistEventArgs::class, $entity);
        $listener->postPersist($event);

        self::assertTrue(true);
    }

    public function testItSkipsUntrackedEntityOnPreRemove(): void
    {
        $listener = new IndexListener(
            $this->client,
            ['App\Entity\Unrelated'],
            $this->logger,
        );

        $entity = new \stdClass();

        $this->client->expects(self::never())->method('collection');

        $event = $this->createEvent(PreRemoveEventArgs::class, $entity);
        $listener->preRemove($event);
    }

    public function testItCatchesTypesenseErrorOnPreRemoveAndLogsWarning(): void
    {
        $entity = new class {
            public int $id = 42;
        };

        $metadata = new DocumentMetadata(
            collection: 'products',
            className: $entity::class,
            idProperty: 'id',
            idType: new IntType(),
            fields: [],
        );

        $registry = $this->createMock(MetadataRegistryInterface::class);
        $registry->method('get')->willReturn($metadata);

        $collection = $this->createMock(CollectionInterface::class);
        $collection->method('delete')->willThrowException(
            new TypesenseClientError('Not found'),
        );

        $this->client->method('collection')->willReturn($collection);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(self::stringContains('Not found'));

        $listener = new IndexListener(
            $this->client,
            [$entity::class],
            $this->logger,
            $registry,
        );

        $event = $this->createEvent(PreRemoveEventArgs::class, $entity);
        $listener->preRemove($event);
    }

    /**
     * @template T of PostPersistEventArgs|PostUpdateEventArgs|PreRemoveEventArgs
     * @param class-string<T> $eventClass
     * @return T
     */
    private function createEvent(string $eventClass, object $entity): PostPersistEventArgs|PostUpdateEventArgs|PreRemoveEventArgs
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->method('getName')->willReturn($entity::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($classMetadata);

        return new $eventClass($entity, $em);
    }
}
