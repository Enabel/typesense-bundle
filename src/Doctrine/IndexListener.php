<?php

declare(strict_types=1);

namespace Enabel\Typesense\Doctrine;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Enabel\Typesense\ClientInterface;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Psr\Log\LoggerInterface;
use Typesense\Exceptions\TypesenseClientError;

final readonly class IndexListener
{
    /** @var array<class-string, true> */
    private array $trackedClasses;

    /**
     * @param class-string[] $classNames
     */
    public function __construct(
        private ClientInterface $client,
        array $classNames,
        private ?LoggerInterface $logger = null,
        private ?MetadataRegistryInterface $registry = null,
    ) {
        $this->trackedClasses = array_fill_keys($classNames, true);
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $this->handleUpsert($event->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $this->handleUpsert($event->getObject());
    }

    public function preRemove(PreRemoveEventArgs $event): void
    {
        $entity = $event->getObject();
        $className = $entity::class;

        if (!isset($this->trackedClasses[$className])) {
            return;
        }

        try {
            assert($this->registry !== null, 'MetadataRegistryInterface is required for preRemove');
            $metadata = $this->registry->get($className);
            $id = (new \ReflectionProperty($entity, $metadata->idProperty))->getValue($entity);
            $this->client->collection($className)->delete((string) $id);
        } catch (TypesenseClientError $e) {
            $this->logger?->warning(\sprintf('Typesense indexing error: %s', $e->getMessage()));
        }
    }

    private function handleUpsert(object $entity): void
    {
        $className = $entity::class;

        if (!isset($this->trackedClasses[$className])) {
            return;
        }

        try {
            $this->client->collection($className)->upsert($entity);
        } catch (TypesenseClientError $e) {
            $this->logger?->warning(\sprintf('Typesense indexing error: %s', $e->getMessage()));
        }
    }
}
