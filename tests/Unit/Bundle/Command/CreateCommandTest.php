<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Bundle\Command;

use Enabel\Typesense\Bundle\Command\CreateCommand;
use Enabel\Typesense\ClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateCommandTest extends TestCase
{
    public function testItCreatesAllCollections(): void
    {
        $client = $this->createMock(ClientInterface::class);

        $client->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(function (string $class): void {
                static $calls = [];
                $calls[] = $class;
            });

        $command = new CreateCommand($client, ['App\Entity\Product', 'App\Entity\User']);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('App\Entity\Product', $tester->getDisplay());
        self::assertStringContainsString('App\Entity\User', $tester->getDisplay());
    }

    public function testItCreatesOnlyTheSpecifiedClass(): void
    {
        $client = $this->createMock(ClientInterface::class);

        $client->expects(self::once())
            ->method('create')
            ->with('App\Entity\Product');

        $command = new CreateCommand($client, ['App\Entity\Product', 'App\Entity\User']);
        $tester = new CommandTester($command);
        $tester->execute(['--class' => 'App\Entity\Product']);

        self::assertSame(0, $tester->getStatusCode());
    }
}
