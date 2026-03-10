<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Enabel\Typesense\Doctrine\DoctrineDataProvider;
use PHPUnit\Framework\TestCase;

final class DoctrineDataProviderTest extends TestCase
{
    public function testItProvidesEntitiesAndDetachesThem(): void
    {
        $entityA = new \stdClass();
        $entityB = new \stdClass();

        $query = $this->createMock(Query::class);
        $query->method('toIterable')->willReturn(new \ArrayIterator([$entityA, $entityB]));

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $em->expects(self::exactly(2))
            ->method('detach')
            ->willReturnCallback(function (object $entity) use ($entityA, $entityB): void {
                static $callIndex = 0;
                if ($callIndex === 0) {
                    self::assertSame($entityA, $entity);
                } else {
                    self::assertSame($entityB, $entity);
                }
                $callIndex++;
            });

        $provider = new DoctrineDataProvider($em);
        $results = iterator_to_array($provider->provide('App\Entity\Product'));

        self::assertCount(2, $results);
        self::assertSame($entityA, $results[0]);
        self::assertSame($entityB, $results[1]);
    }
}
