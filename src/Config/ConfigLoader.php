<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class ConfigLoader
 *
 * Loads and validates configuration for code quality metrics.
 */
class ConfigLoader implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('config');

        $rootNode = $treeBuilder->getRootNode();

        /* @phpstan-ignore-next-line */
        $rootNode
            ->children()
                ->arrayNode('excludePatterns')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('cognitive')
                    ->children()
                        ->arrayNode('excludedClasses')
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('excludedMethods')
                            ->scalarPrototype()->end()
                        ->end()
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
                    ->end()
                ->end()
                ->arrayNode('halstead')
                    ->children()
                        ->arrayNode('excludedClasses')
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('excludedMethods')
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('excludePatterns')
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('threshold')
                            ->children()
                                ->floatNode('difficulty')->end()
                                ->floatNode('effort')->end()
                                ->floatNode('time')->end()
                                ->floatNode('bugs')->end()
                                ->floatNode('volume')->end()
                            ->end()
                        ->end()
                    ->end()
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
