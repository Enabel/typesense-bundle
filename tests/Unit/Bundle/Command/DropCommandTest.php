<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Bundle\Command;

use Enabel\Typesense\Bundle\Command\DropCommand;
use Enabel\Typesense\ClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DropCommandTest extends TestCase
{
    public function testItFailsWithoutForceOption(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('drop');

        $command = new DropCommand($client, ['App\Entity\Product']);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('--force', $tester->getDisplay());
    }

    public function testItDropsAllCollectionsWithForceOption(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::exactly(2))->method('drop');

        $command = new DropCommand($client, ['App\Entity\Product', 'App\Entity\User']);
        $tester = new CommandTester($command);
        $tester->execute(['--force' => true]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('App\Entity\Product', $tester->getDisplay());
        self::assertStringContainsString('App\Entity\User', $tester->getDisplay());
    }

    public function testItDropsOnlyTheSpecifiedClass(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())
            ->method('drop')
            ->with('App\Entity\User');

        $command = new DropCommand($client, ['App\Entity\Product', 'App\Entity\User']);
        $tester = new CommandTester($command);
        $tester->execute(['--class' => 'App\Entity\User', '--force' => true]);

        self::assertSame(0, $tester->getStatusCode());
    }
}
