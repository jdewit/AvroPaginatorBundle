<?php

namespace Avro\PaginatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('avro_paginator');

        $rootNode
            ->children()
                ->arrayNode('paginators')
                    ->useAttributeAsKey('paginator')->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('db_driver')->defaultValue('mongodb')->cannotBeEmpty()->end()
                            ->scalarNode('default_limit')->defaultValue(20)->cannotBeEmpty()->end()
                            ->scalarNode('default_direction')->defaultValue('asc')->cannotBeEmpty()->end()
                            ->scalarNode('max_limit')->defaultValue(1000)->cannotBeEmpty()->end()
                            ->scalarNode('button_count')->defaultValue(9)->cannotBeEmpty()->end()
                            ->arrayNode('limit_steps')
                                ->prototype('scalar')->end()
                                ->defaultValue(array(20, 50, 500, 1000))
                            ->end()
                        ->end()
                    ->end()
                ->end()
             ->end();

        return $treeBuilder;
    }
}
