<?php

namespace Kefisu\Bundle\MaintenanceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('maintenance');
        $rootNode = $treeBuilder->getRootNode();

        if ($rootNode instanceof ArrayNodeDefinition === false) {
            return $treeBuilder;
        }

        $rootNode
            ->children()
                ->scalarNode('maintenance_file_path')
                    ->defaultNull()
                    ->info('The path to the file that will be used to store the maintenance data.')
                ->end();

        return $treeBuilder;
    }
}
