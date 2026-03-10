<?php

declare(strict_types=1);

namespace Enabel\Typesense\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Enabel\Typesense\Document\DataProviderInterface;

final readonly class DoctrineDataProvider implements DataProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function provide(string $className): iterable
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from($className, 'e')
            ->getQuery();

        foreach ($query->toIterable() as $entity) {
            yield $entity;
            $this->entityManager->detach($entity);
        }
    }
}
