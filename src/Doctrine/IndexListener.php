<?php

declare(strict_types=1);

namespace Enabel\Typesense\Doctrine;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\Persistence\ObjectManager;
use Enabel\Typesense\ClientInterface;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Psr\Log\LoggerInterface;

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
        $this->handleUpsert($event->getObject(), $event->getObjectManager());
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $this->handleUpsert($event->getObject(), $event->getObjectManager());
    }

    public function preRemove(PreRemoveEventArgs $event): void
    {
        $entity = $event->getObject();
        $className = $this->resolveClassName($entity, $event->getObjectManager());

        if ($className === null) {
            return;
        }

        try {
            assert($this->registry !== null, 'MetadataRegistryInterface is required for preRemove');
            $metadata = $this->registry->get($className);
            $id = (new \ReflectionClass($metadata->className))->getProperty($metadata->idProperty)->getValue($entity);
            $this->client->collection($className)->delete((string) $id);
        } catch (\Throwable $e) {
            $this->logger?->warning(\sprintf('Typesense indexing error: %s', $e->getMessage()));
        }
    }

    /**
     * @return class-string|null
     */
    private function resolveClassName(object $entity, ObjectManager $om): ?string
    {
        $className = $om->getClassMetadata($entity::class)->getName();

        return isset($this->trackedClasses[$className]) ? $className : null;
    }

    private function handleUpsert(object $entity, ObjectManager $om): void
    {
        $className = $this->resolveClassName($entity, $om);

        if ($className === null) {
            return;
        }

        try {
            $this->client->collection($className)->upsert($entity);
        } catch (\Throwable $e) {
            $this->logger?->warning(\sprintf('Typesense indexing error: %s', $e->getMessage()));
        }
    }
}
