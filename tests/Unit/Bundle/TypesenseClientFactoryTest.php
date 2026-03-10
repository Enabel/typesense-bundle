<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Bundle;

use Enabel\Typesense\Bundle\TypesenseClientFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Typesense\Client;

#[CoversClass(TypesenseClientFactory::class)]
final class TypesenseClientFactoryTest extends TestCase
{
    public function testCreateReturnsClient(): void
    {
        $client = TypesenseClientFactory::create('https://ts.example.com:443', 'my-key');

        self::assertInstanceOf(Client::class, $client);
    }

    public function testCreateWithDefaultPort(): void
    {
        $client = TypesenseClientFactory::create('http://localhost', 'test-key');

        self::assertInstanceOf(Client::class, $client);
    }
}
