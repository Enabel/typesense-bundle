<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Enabel\Typesense\Doctrine\DoctrineDenormalizer;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Enabel\Typesense\Type\IntType;
use Enabel\Typesense\Type\StringType;
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
            collection: 'products',
            className: 'App\Entity\Product',
            idProperty: 'id',
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
            collection: 'products',
            className: 'App\Entity\Product',
            idProperty: 'uuid',
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

    public function testItIndexesEntitiesByNormalizedStringIdForObjectIds(): void
    {
        // Reproduces the Symfony Ulid case: the entity id property holds an object,
        // which cannot be used as an array key. The denormalizer must index by the
        // normalized id cast to string and still return entities in document order.
        $metadata = new DocumentMetadata(
            collection: 'products',
            className: 'App\Entity\Product',
            idProperty: 'id',
            idType: new StringType(),
            fields: [],
        );

        $this->registry->method('get')->willReturn($metadata);

        $makeId = static fn (string $value): \Stringable => new class($value) implements \Stringable {
            public function __construct(private readonly string $value)
            {
            }

            public function __toString(): string
            {
                return $this->value;
            }
        };

        $entityA = new \stdClass();
        $entityA->id = $makeId('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $entityB = new \stdClass();
        $entityB->id = $makeId('01BX5ZZKBKACTAV9WEVGEMMVRZ');

        $repo = $this->createMock(EntityRepository::class);
        $this->em->method('getRepository')->willReturn($repo);

        $repo->expects(self::once())
            ->method('findBy')
            ->with(['id' => ['01ARZ3NDEKTSV4RRFFQ69G5FAV', '01BX5ZZKBKACTAV9WEVGEMMVRZ']])
            ->willReturn([$entityA, $entityB]);

        $documents = [
            ['id' => '01ARZ3NDEKTSV4RRFFQ69G5FAV', 'title' => 'A'],
            ['id' => '01BX5ZZKBKACTAV9WEVGEMMVRZ', 'title' => 'B'],
        ];

        $result = $this->denormalizer->denormalize($documents, 'App\Entity\Product');

        self::assertSame([$entityA, $entityB], $result);
    }

    public function testItDenormalizesIdUsingIdType(): void
    {
        $metadata = new DocumentMetadata(
            collection: 'products',
            className: 'App\Entity\Product',
            idProperty: 'id',
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
