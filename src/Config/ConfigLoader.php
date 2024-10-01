<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\MetricNames;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class ConfigLoader
 *
 * Loads and validates configuration for code quality metrics.
 */
class ConfigLoader implements ConfigurationInterface
{
    private const THRESHOLD = 'threshold';
    private const SCALE = 'scale';
    private const ENABLED = 'enabled';

    /**
     * Returns the default configuration for cognitive metrics.
     *
     * @return array<string, array<string, bool|float|int>>
     */
    public function getCognitiveMetricDefaults(): array
    {
        return [
            MetricNames::LINE_COUNT->value => [
                self::THRESHOLD => 60,
                self::SCALE => 25.0,
                self::ENABLED => true
            ],
            MetricNames::ARG_COUNT->value => [
                self::THRESHOLD => 4,
                self::SCALE => 1.0,
                self::ENABLED => true
            ],
            MetricNames::RETURN_COUNT->value => [
                self::THRESHOLD => 2,
                self::SCALE => 5.0,
                self::ENABLED => true
            ],
            MetricNames::VARIABLE_COUNT->value => [
                self::THRESHOLD => 2,
                self::SCALE => 5.0,
                self::ENABLED => true
            ],
            MetricNames::PROPERTY_CALL_COUNT->value => [
                self::THRESHOLD => 2,
                self::SCALE => 15.0,
                self::ENABLED => true
            ],
            MetricNames::IF_COUNT->value => [
                self::THRESHOLD => 3,
                self::SCALE => 1.0,
                self::ENABLED => true
            ],
            MetricNames::IF_NESTING_LEVEL->value => [
                self::THRESHOLD => 1,
                self::SCALE => 1.0,
                self::ENABLED => true
            ],
            MetricNames::ELSE_COUNT->value => [
                self::THRESHOLD => 1,
                self::SCALE => 1.0,
                self::ENABLED => true
            ],
        ];
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('config');
        $rootNode = $treeBuilder->getRootNode();

        /* @phpstan-ignore-next-line */
        $rootNode
            ->children()
                ->arrayNode('cognitive')
                    ->children()
                        ->arrayNode('excludePatterns')
                            ->scalarPrototype()
                            ->defaultValue([])
                            ->end()
                        ->end()
                        ->arrayNode('metrics')
                            ->useAttributeAsKey('metric')
                            ->arrayPrototype()
                                ->children()
                                    ->floatNode('threshold')
                                        ->end()
                                    ->floatNode('scale')
                                        ->defaultValue(1.0)
                                        ->end()
                                    ->booleanNode('enabled')
                                        ->defaultValue(true)
                                        ->end()
                                ->end()
                            ->end()
                            ->beforeNormalization()
                                ->ifArray()
                                ->then(function ($mapping) {
                                    return $mapping + $this->getCognitiveMetricDefaults();
                                })
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('halstead')
                    ->children()
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
            ->end();

        return $treeBuilder;
    }
}
