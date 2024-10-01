<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigLoader;

/**
 * Class ConfigLoaderTest
 *
 * Unit tests for the ConfigLoader.
 */
class ConfigLoaderTest extends TestCase
{
    public function testConfigTreeBuilder(): void
    {
        $configLoader = new ConfigLoader();
        $processor = new Processor();
        $treeBuilder = $configLoader->getConfigTreeBuilder();
        $configTree = $treeBuilder->buildTree();

        $config = [
            'cognitive' => [
                'metrics' => [
                    'lineCount' => [
                        'threshold' => 60.0,
                        'scale' => 25.0,
                    ],
                    'argCount' => [
                        'threshold' => 4.0,
                        'scale' => 1.0,
                    ],
                ],
            ],
            'halstead' => [
                'threshold' => [
                    'difficulty' => 5.0,
                    'effort' => 3.0,
                    'time' => 2.0,
                    'bugs' => 1.0,
                    'volume' => 4.0,
                ],
            ],
        ];

        $processedConfig = $processor->process($configTree, [$config]);

        $this->assertArrayHasKey('cognitive', $processedConfig);
        $this->assertArrayHasKey('halstead', $processedConfig);

        // Assertions for 'cognitive' metrics
        $this->assertArrayHasKey('lineCount', $processedConfig['cognitive']['metrics']);
        $this->assertEquals(60.0, $processedConfig['cognitive']['metrics']['lineCount']['threshold']);
        $this->assertEquals(25.0, $processedConfig['cognitive']['metrics']['lineCount']['scale']);

        // Assertions for 'halstead' thresholds
        $this->assertArrayHasKey('threshold', $processedConfig['halstead']);
        $this->assertEquals(5.0, $processedConfig['halstead']['threshold']['difficulty']);
        $this->assertEquals(3.0, $processedConfig['halstead']['threshold']['effort']);
    }

    public function testEmptyConfig(): void
    {
        $configLoader = new ConfigLoader();
        $processor = new Processor();
        $treeBuilder = $configLoader->getConfigTreeBuilder();
        $configTree = $treeBuilder->buildTree();

        $processedConfig = $processor->process($configTree, []);

        $this->assertEmpty($processedConfig['cognitive']['metrics']);
        $this->assertEmpty($processedConfig['halstead']['threshold']);
    }

    public function testInvalidConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $configLoader = new ConfigLoader();
        $processor = new Processor();
        $treeBuilder = $configLoader->getConfigTreeBuilder();
        $configTree = $treeBuilder->buildTree();

        $config = [
            'metrics' => [
                'lineCount' => [
                    'threshold' => 'invalid', // This should be a float
                    'scale' => 2.0,
                ],
            ],
        ];

        $processor->process($configTree, [$config]);
    }
}
