<?php

declare(strict_types=1);

namespace Enabel\Typesense\Bundle\DependencyInjection;

use Enabel\Typesense\Bundle\Command\CreateCommand;
use Enabel\Typesense\Bundle\Command\DropCommand;
use Enabel\Typesense\Bundle\Command\ImportCommand;
use Enabel\Typesense\Bundle\Command\SearchCommand;
use Enabel\Typesense\Bundle\TypesenseClientFactory;
use Enabel\Typesense\Client;
use Enabel\Typesense\ClientInterface;
use Enabel\Typesense\Doctrine\DoctrineDataProvider;
use Enabel\Typesense\Doctrine\DoctrineDenormalizer;
use Enabel\Typesense\Doctrine\IndexListener;
use Enabel\Typesense\Document\DocumentNormalizer;
use Enabel\Typesense\Document\DocumentNormalizerInterface;
use Enabel\Typesense\Metadata\CachedMetadataRegistry;
use Enabel\Typesense\Metadata\MetadataReader;
use Enabel\Typesense\Metadata\MetadataReaderInterface;
use Enabel\Typesense\Metadata\MetadataRegistry;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Enabel\Typesense\Schema\SchemaBuilder;
use Enabel\Typesense\Schema\SchemaBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class EnabelTypesenseExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerTypesenseClient($container, $config['client']);
        $this->registerCoreServices($container, $config['collection_prefix']);
        $this->registerDoctrineServices($container, $config['auto_index']);
        $dataProviderMap = $this->registerCollections($container, $config);
        $this->registerCommands($container, $dataProviderMap);
    }

    /**
     * @param array{url: string, api_key: string} $clientConfig
     */
    private function registerTypesenseClient(ContainerBuilder $container, array $clientConfig): void
    {
        $container->register('enabel_typesense.typesense_client', \Typesense\Client::class)
            ->setFactory([TypesenseClientFactory::class, 'create'])
            ->addArgument($clientConfig['url'])
            ->addArgument($clientConfig['api_key']);
    }

    private function registerCoreServices(ContainerBuilder $container, string $collectionPrefix): void
    {
        $container->register(MetadataReaderInterface::class, MetadataReader::class);

        $container->register(MetadataRegistry::class)
            ->addArgument(new Reference(MetadataReaderInterface::class))
            ->addArgument($collectionPrefix);

        $container->register(MetadataRegistryInterface::class, CachedMetadataRegistry::class)
            ->addArgument(new Reference(MetadataRegistry::class))
            ->addArgument(new Reference('cache.system'));

        $container->register(DocumentNormalizerInterface::class, DocumentNormalizer::class)
            ->addArgument(new Reference(MetadataRegistryInterface::class));

        $container->register(SchemaBuilderInterface::class, SchemaBuilder::class);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<class-string, Reference>
     */
    private function registerCollections(ContainerBuilder $container, array $config): array
    {
        $doctrineAvailable = interface_exists(\Doctrine\ORM\EntityManagerInterface::class);

        $defaultDenormalizer = $config['default_denormalizer']
            ?? ($doctrineAvailable ? DoctrineDenormalizer::class : null);
        $defaultDataProvider = $config['default_data_provider']
            ?? ($doctrineAvailable ? DoctrineDataProvider::class : null);
        $denormalizerMap = [];
        $dataProviderMap = [];

        foreach ($config['collections'] as $className => $collectionConfig) {
            $denormalizerService = $collectionConfig['denormalizer'] ?? $defaultDenormalizer;
            $dataProviderService = $collectionConfig['data_provider'] ?? $defaultDataProvider;

            if ($denormalizerService !== null) {
                $denormalizerMap[$className] = new Reference($denormalizerService);
            }

            if ($dataProviderService !== null) {
                $dataProviderMap[$className] = new Reference($dataProviderService);
            }
        }

        $container->register(ClientInterface::class, Client::class)
            ->addArgument(new Reference('enabel_typesense.typesense_client'))
            ->addArgument(new Reference(MetadataRegistryInterface::class))
            ->addArgument(new Reference(DocumentNormalizerInterface::class))
            ->addArgument(new Reference(SchemaBuilderInterface::class))
            ->addArgument($denormalizerMap);

        $container->setParameter('enabel_typesense.collection_classes', array_keys($config['collections']));

        return $dataProviderMap;
    }

    /**
     * @param array<class-string, Reference> $dataProviderMap
     */
    private function registerCommands(ContainerBuilder $container, array $dataProviderMap): void
    {
        $container->register(CreateCommand::class)
            ->addArgument(new Reference(ClientInterface::class))
            ->addArgument('%enabel_typesense.collection_classes%')
            ->addTag('console.command');

        $container->register(DropCommand::class)
            ->addArgument(new Reference(ClientInterface::class))
            ->addArgument('%enabel_typesense.collection_classes%')
            ->addTag('console.command');

        $container->register(ImportCommand::class)
            ->addArgument(new Reference(ClientInterface::class))
            ->addArgument('%enabel_typesense.collection_classes%')
            ->addArgument($dataProviderMap)
            ->addTag('console.command');

        $container->register(SearchCommand::class)
            ->addArgument(new Reference(ClientInterface::class))
            ->addArgument(new Reference(MetadataRegistryInterface::class))
            ->addTag('console.command');
    }

    private function registerDoctrineServices(ContainerBuilder $container, bool $autoIndex): void
    {
        if (!interface_exists(\Doctrine\ORM\EntityManagerInterface::class)) {
            return;
        }

        $container->register(DoctrineDenormalizer::class)
            ->addArgument(new Reference('doctrine.orm.entity_manager'))
            ->addArgument(new Reference(MetadataRegistryInterface::class));

        $container->register(DoctrineDataProvider::class)
            ->addArgument(new Reference('doctrine.orm.entity_manager'));

        if (!$autoIndex) {
            return;
        }

        $container->register(IndexListener::class)
            ->addArgument(new Reference(ClientInterface::class))
            ->addArgument('%enabel_typesense.collection_classes%')
            ->addArgument(new Reference('logger', ContainerBuilder::NULL_ON_INVALID_REFERENCE))
            ->addArgument(new Reference(MetadataRegistryInterface::class))
            ->addTag('doctrine.event_listener', ['event' => 'postPersist'])
            ->addTag('doctrine.event_listener', ['event' => 'postUpdate'])
            ->addTag('doctrine.event_listener', ['event' => 'preRemove']);
    }
}
