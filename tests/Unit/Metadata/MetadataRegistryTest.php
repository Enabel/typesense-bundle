<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Metadata;

use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Metadata\MetadataReaderInterface;
use Enabel\Typesense\Metadata\MetadataRegistry;
use Enabel\Typesense\Tests\Fixtures\ValidProduct;
use Enabel\Typesense\Type\IntType;
use PHPUnit\Framework\TestCase;

final class MetadataRegistryTest extends TestCase
{
    public function testItDelegatesToTheReader(): void
    {
        $expected = new DocumentMetadata(
            className: ValidProduct::class,
            collection: 'products',
            defaultSortingField: null,
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [],
        );

        $reader = $this->createMock(MetadataReaderInterface::class);
        $reader->expects(self::once())
            ->method('read')
            ->with(ValidProduct::class)
            ->willReturn($expected);

        $registry = new MetadataRegistry($reader);

        self::assertSame($expected, $registry->get(ValidProduct::class));
    }

    public function testItCachesMetadataPerClassName(): void
    {
        $expected = new DocumentMetadata(
            className: ValidProduct::class,
            collection: 'products',
            defaultSortingField: null,
            idPropertyName: 'id',
            idType: new IntType(),
            fields: [],
        );

        $reader = $this->createMock(MetadataReaderInterface::class);
        $reader->expects(self::once())
            ->method('read')
            ->with(ValidProduct::class)
            ->willReturn($expected);

        $registry = new MetadataRegistry($reader);

        $first = $registry->get(ValidProduct::class);
        $second = $registry->get(ValidProduct::class);

        self::assertSame($first, $second);
    }
}
