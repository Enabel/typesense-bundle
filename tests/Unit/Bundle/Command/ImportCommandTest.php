<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Bundle\Command;

use Enabel\Typesense\Bundle\Command\ImportCommand;
use Enabel\Typesense\ClientInterface;
use Enabel\Typesense\CollectionInterface;
use Enabel\Typesense\Document\DataProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ImportCommandTest extends TestCase
{
    public function testItImportsDocumentsInBatches(): void
    {
        $entities = array_map(fn(int $i) => (object) ['id' => $i], range(1, 150));

        $provider = $this->createMock(DataProviderInterface::class);
        $provider->method('provide')->willReturn(new \ArrayIterator($entities));

        $collection = $this->createMock(CollectionInterface::class);
        // 100 + 50 = 2 import calls
        $collection->expects(self::exactly(2))->method('import');

        $client = $this->createMock(ClientInterface::class);
        $client->method('collection')->willReturn($collection);

        $command = new ImportCommand(
            $client,
            ['App\Entity\Product'],
            ['App\Entity\Product' => $provider],
        );
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('150 documents', $tester->getDisplay());
    }

    public function testItSkipsClassWithoutDataProvider(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('collection');

        $command = new ImportCommand($client, ['App\Entity\Product']);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('No data provider', $tester->getDisplay());
    }

    public function testItImportsOnlyTheSpecifiedClass(): void
    {
        $provider = $this->createMock(DataProviderInterface::class);
        $provider->method('provide')->willReturn(new \ArrayIterator([(object) ['id' => 1]]));

        $collection = $this->createMock(CollectionInterface::class);
        $collection->expects(self::once())->method('import');

        $client = $this->createMock(ClientInterface::class);
        $client->method('collection')
            ->with('App\Entity\User')
            ->willReturn($collection);

        $command = new ImportCommand(
            $client,
            ['App\Entity\Product', 'App\Entity\User'],
            [
                'App\Entity\Product' => $this->createMock(DataProviderInterface::class),
                'App\Entity\User' => $provider,
            ],
        );
        $tester = new CommandTester($command);
        $tester->execute(['--class' => 'App\Entity\User']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('1 documents', $tester->getDisplay());
    }
}
