<?php

declare(strict_types=1);

namespace Enabel\Typesense\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('enabel_typesense');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('client')
                    ->isRequired()
                    ->children()
                        ->scalarNode('url')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('api_key')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->scalarNode('default_denormalizer')->defaultNull()->end()
                ->scalarNode('default_data_provider')->defaultNull()->end()
                ->arrayNode('collections')
                    ->useAttributeAsKey('class')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('denormalizer')->defaultNull()->end()
                            ->scalarNode('data_provider')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
