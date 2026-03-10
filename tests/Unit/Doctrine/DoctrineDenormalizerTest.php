<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Enabel\Typesense\Doctrine\DoctrineDenormalizer;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Enabel\Typesense\Type\IntType;
use PHPUnit\Framework\TestCase;

final class DoctrineDenormalizerTest extends TestCase
{
    private EntityManagerInterface $em;
    private MetadataRegistryInterface $registry;
    private DoctrineDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->registry = $this->createMock(MetadataRegistryInterface::class);
        $this->denormalizer = new DoctrineDenormalizer($this->em, $this->registry);
    }

    public function testItReturnsEmptyArrayForEmptyDocuments(): void
    {
        $result = $this->denormalizer->denormalize([], 'App\Entity\Product');

        self::assertSame([], $result);
    }

    public function testItFetchesEntitiesByIdAndReturnsInOrder(): void
    {
        $metadata = new DocumentMetadata(
            className: 'App\Entity\Product',
            collection: 'products',
            defaultSortingField: null,
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [],
        );

        $this->registry->method('get')->willReturn($metadata);

        $entityA = new \stdClass();
        $entityA->id = 1;
        $entityB = new \stdClass();
        $entityB->id = 3;

        $repo = $this->createMock(EntityRepository::class);
        $this->em->method('getRepository')->willReturn($repo);

        $repo->expects(self::once())
            ->method('findBy')
            ->with(['id' => [1, 2, 3]])
            ->willReturn([$entityA, $entityB]);

        $documents = [
            ['id' => '1', 'title' => 'A'],
            ['id' => '2', 'title' => 'B'],
            ['id' => '3', 'title' => 'C'],
        ];

        $result = $this->denormalizer->denormalize($documents, 'App\Entity\Product');

        self::assertCount(3, $result);
        self::assertSame($entityA, $result[0]);
        self::assertNull($result[1]); // deleted entity
        self::assertSame($entityB, $result[2]);
    }

    public function testItUsesIdPropertyNameFromMetadata(): void
    {
        $metadata = new DocumentMetadata(
            className: 'App\Entity\Product',
            collection: 'products',
            defaultSortingField: null,
            idPropertyName: 'uuid',
            idType: new IntType(),
            fields: [],
        );

        $this->registry->method('get')->willReturn($metadata);

        $repo = $this->createMock(EntityRepository::class);
        $this->em->method('getRepository')->willReturn($repo);

        $repo->expects(self::once())
            ->method('findBy')
            ->with(['uuid' => [42]])
            ->willReturn([]);

        $result = $this->denormalizer->denormalize(
            [['id' => '42', 'title' => 'X']],
            'App\Entity\Product',
        );

        self::assertSame([null], $result);
    }

    public function testItDenormalizesIdUsingIdType(): void
    {
        $metadata = new DocumentMetadata(
            className: 'App\Entity\Product',
            collection: 'products',
            defaultSortingField: null,
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [],
        );

        $this->registry->method('get')->willReturn($metadata);

        $repo = $this->createMock(EntityRepository::class);
        $this->em->method('getRepository')->willReturn($repo);

        // The IDs should be denormalized (string "5" → int 5)
        $repo->expects(self::once())
            ->method('findBy')
            ->with(['id' => [5]])
            ->willReturn([]);

        $this->denormalizer->denormalize(
            [['id' => '5', 'name' => 'Test']],
            'App\Entity\Product',
        );
    }
}
