<?php

declare(strict_types=1);

namespace Phauthentic\CodeQuality\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 *
 */
class ConfigLoader implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('config');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('excludePatterns')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('metrics')
                    ->useAttributeAsKey('metric')
                    ->arrayPrototype()
                        ->children()
                            ->floatNode('threshold')->end()
                            ->floatNode('scale')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
