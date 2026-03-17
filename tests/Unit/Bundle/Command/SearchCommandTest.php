<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Bundle\Command;

use Enabel\Typesense\Bundle\Command\SearchCommand;
use Enabel\Typesense\ClientInterface;
use Enabel\Typesense\CollectionInterface;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Enabel\Typesense\Type\IntType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SearchCommandTest extends TestCase
{
    private ClientInterface $client;
    private MetadataRegistryInterface $registry;
    private CollectionInterface $collection;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->registry = $this->createMock(MetadataRegistryInterface::class);
        $this->collection = $this->createMock(CollectionInterface::class);

        $this->client->method('collection')->willReturn($this->collection);

        $metadata = new DocumentMetadata(
            collection: 'products',
            className: 'App\Entity\Product',
            idProperty: 'id',
            idType: new IntType(),
            fields: [],
        );
        $this->registry->method('get')->willReturn($metadata);
    }

    public function testItSearchesWithDefaultWildcard(): void
    {
        $this->collection->expects(self::once())
            ->method('searchRaw')
            ->with(self::callback(function (array $params): bool {
                return $params['q'] === '*'
                    && $params['query_by'] === ''
                    && !isset($params['filter_by']);
            }))
            ->willReturn(['found' => 0, 'hits' => []]);

        $tester = $this->execute([
            'class' => 'App\Entity\Product',
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('"found": 0', $tester->getDisplay());
    }

    public function testItSearchesWithProvidedQuery(): void
    {
        $this->collection->expects(self::once())
            ->method('searchRaw')
            ->with(self::callback(function (array $params): bool {
                return $params['q'] === 'wireless mouse'
                    && $params['query_by'] === 'title,description';
            }))
            ->willReturn(['found' => 1, 'hits' => [['document' => ['id' => '1', 'title' => 'Wireless Mouse']]]]);

        $tester = $this->execute([
            'class' => 'App\Entity\Product',
            '--query' => 'wireless mouse',
            '--query-by' => 'title,description',
        ]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('"found": 1', $tester->getDisplay());
        self::assertStringContainsString('Wireless Mouse', $tester->getDisplay());
    }

    public function testItSearchesWithFilterOption(): void
    {
        $this->collection->expects(self::once())
            ->method('searchRaw')
            ->with(self::callback(function (array $params): bool {
                return $params['filter_by'] === 'price:>10';
            }))
            ->willReturn(['found' => 0, 'hits' => []]);

        $tester = $this->execute([
            'class' => 'App\Entity\Product',
            '--filter' => 'price:>10',
        ]);

        self::assertSame(0, $tester->getStatusCode());
    }

    public function testItSearchesWithPerPageOption(): void
    {
        $this->collection->expects(self::once())
            ->method('searchRaw')
            ->with(self::callback(function (array $params): bool {
                return $params['per_page'] === 5;
            }))
            ->willReturn(['found' => 0, 'hits' => []]);

        $tester = $this->execute([
            'class' => 'App\Entity\Product',
            '--per-page' => '5',
        ]);

        self::assertSame(0, $tester->getStatusCode());
    }

    /**
     * @param array<string, string> $input
     */
    private function execute(array $input): CommandTester
    {
        $command = new SearchCommand($this->client, $this->registry);
        $tester = new CommandTester($command);
        $tester->execute($input);

        return $tester;
    }
}
