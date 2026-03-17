<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Metadata;

use Enabel\Typesense\Metadata\CachedMetadataRegistry;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Enabel\Typesense\Tests\Fixtures\ValidProduct;
use Enabel\Typesense\Type\IntType;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

final class CachedMetadataRegistryTest extends TestCase
{
    public function testItDelegatesToCacheAndInnerRegistry(): void
    {
        $expected = new DocumentMetadata(
            collection: 'products',
            className: ValidProduct::class,
            idProperty: 'id',
            idType: new IntType(),
            fields: [],
        );

        $inner = $this->createMock(MetadataRegistryInterface::class);
        $inner->expects(self::once())
            ->method('get')
            ->with(ValidProduct::class)
            ->willReturn($expected);

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::exactly(2))
            ->method('get')
            ->with('typesense_metadata_' . md5(ValidProduct::class), self::isType('callable'))
            ->willReturnCallback(function (string $key, callable $callback) use (&$cached) {
                return $cached ??= $callback();
            });

        $registry = new CachedMetadataRegistry($inner, $cache);

        $first = $registry->get(ValidProduct::class);
        $second = $registry->get(ValidProduct::class);

        self::assertSame($expected, $first);
        self::assertSame($first, $second);
    }
}
