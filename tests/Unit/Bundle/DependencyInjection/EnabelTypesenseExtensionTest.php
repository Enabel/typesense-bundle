<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Bundle\DependencyInjection;

use Enabel\Typesense\Bundle\Command\CreateCommand;
use Enabel\Typesense\Bundle\Command\DropCommand;
use Enabel\Typesense\Bundle\Command\ImportCommand;
use Enabel\Typesense\Bundle\DependencyInjection\EnabelTypesenseExtension;
use Enabel\Typesense\Bundle\TypesenseClientFactory;
use Enabel\Typesense\ClientInterface;
use Enabel\Typesense\Doctrine\DoctrineDataProvider;
use Enabel\Typesense\Doctrine\DoctrineDenormalizer;
use Enabel\Typesense\Doctrine\IndexListener;
use Enabel\Typesense\Document\DocumentNormalizerInterface;
use Enabel\Typesense\Metadata\MetadataReaderInterface;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Enabel\Typesense\Schema\SchemaBuilderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class EnabelTypesenseExtensionTest extends TestCase
{
    public function testItRegistersCoreServices(): void
    {
        $container = $this->buildContainer();

        self::assertTrue($container->hasDefinition(MetadataReaderInterface::class));
        self::assertTrue($container->hasDefinition(MetadataRegistryInterface::class));
        self::assertTrue($container->hasDefinition(DocumentNormalizerInterface::class));
        self::assertTrue($container->hasDefinition(SchemaBuilderInterface::class));
        self::assertTrue($container->hasDefinition(ClientInterface::class));
        self::assertTrue($container->hasDefinition('enabel_typesense.typesense_client'));
    }

    public function testItRegistersConsoleCommands(): void
    {
        $container = $this->buildContainer();

        self::assertTrue($container->hasDefinition(CreateCommand::class));
        self::assertTrue($container->hasDefinition(DropCommand::class));
        self::assertTrue($container->hasDefinition(ImportCommand::class));

        self::assertTrue($container->getDefinition(CreateCommand::class)->hasTag('console.command'));
        self::assertTrue($container->getDefinition(DropCommand::class)->hasTag('console.command'));
        self::assertTrue($container->getDefinition(ImportCommand::class)->hasTag('console.command'));
    }

    public function testItRegistersDoctrineServicesWhenDoctrineIsPresent(): void
    {
        $container = $this->buildContainer();

        self::assertTrue($container->hasDefinition(DoctrineDenormalizer::class));
        self::assertTrue($container->hasDefinition(DoctrineDataProvider::class));
        self::assertTrue($container->hasDefinition(IndexListener::class));

        $tags = $container->getDefinition(IndexListener::class)->getTags();
        $events = array_map(
            fn(array $attr) => $attr['event'],
            $tags['doctrine.event_listener'] ?? [],
        );

        self::assertContains('postPersist', $events);
        self::assertContains('postUpdate', $events);
        self::assertContains('preRemove', $events);
    }

    public function testItSetsCollectionClassesParameter(): void
    {
        $container = $this->buildContainer();

        self::assertTrue($container->hasParameter('enabel_typesense.collection_classes'));
        $classes = $container->getParameter('enabel_typesense.collection_classes');
        self::assertSame(['App\Entity\Product'], $classes);
    }

    public function testItConfiguresTypesenseClientFromUrl(): void
    {
        $container = $this->buildContainer([
            'client' => [
                'url' => 'https://ts.example.com:443',
                'api_key' => 'my-key',
            ],
            'collections' => [],
        ]);

        $definition = $container->getDefinition('enabel_typesense.typesense_client');

        self::assertSame([TypesenseClientFactory::class, 'create'], $definition->getFactory());
        self::assertSame('https://ts.example.com:443', $definition->getArgument(0));
        self::assertSame('my-key', $definition->getArgument(1));
    }

    public function testItRegistersCustomDenormalizerAndDataProvider(): void
    {
        $container = $this->buildContainer([
            'client' => [
                'url' => 'http://localhost:8108',
                'api_key' => '123',
            ],
            'collections' => [
                'App\Entity\Product' => [
                    'denormalizer' => 'app.custom_denormalizer',
                    'data_provider' => 'app.custom_data_provider',
                ],
            ],
        ]);

        $clientDef = $container->getDefinition(ClientInterface::class);
        $denormalizerMap = $clientDef->getArgument(4);
        self::assertArrayHasKey('App\Entity\Product', $denormalizerMap);
        self::assertSame('app.custom_denormalizer', (string) $denormalizerMap['App\Entity\Product']);

        $importDef = $container->getDefinition(ImportCommand::class);
        $dataProviderMap = $importDef->getArgument(2);
        self::assertArrayHasKey('App\Entity\Product', $dataProviderMap);
        self::assertSame('app.custom_data_provider', (string) $dataProviderMap['App\Entity\Product']);
    }

    /**
     * @param array<string, mixed>|null $config
     */
    private function buildContainer(?array $config = null): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $extension = new EnabelTypesenseExtension();

        $extension->load([
            $config ?? [
                'client' => [
                    'url' => 'http://localhost:8108',
                    'api_key' => '123',
                ],
                'collections' => [
                    'App\Entity\Product' => null,
                ],
            ],
        ], $container);

        return $container;
    }
}
