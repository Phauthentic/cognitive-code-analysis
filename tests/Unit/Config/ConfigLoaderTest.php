<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Phauthentic\CodeQualityMetrics\Config\ConfigLoader;

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
            'excludePatterns' => ['pattern1', 'pattern2'],
            'cognitive' => [
                'excludedClasses' => ['Class1', 'Class2'],
                'excludedMethods' => ['method1', 'method2'],
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
        ];

        $processedConfig = $processor->process($configTree, [$config]);

        $this->assertArrayHasKey('excludePatterns', $processedConfig);
        $this->assertArrayHasKey('cognitive', $processedConfig);
        $this->assertArrayHasKey('halstead', $processedConfig);
        $this->assertArrayHasKey('metrics', $processedConfig);

        // Assertions for 'cognitive' metrics
        $this->assertArrayHasKey('lineCount', $processedConfig['cognitive']['metrics']);
        $this->assertEquals(60.0, $processedConfig['cognitive']['metrics']['lineCount']['threshold']);
        $this->assertEquals(25.0, $processedConfig['cognitive']['metrics']['lineCount']['scale']);

        // Assertions for 'halstead' thresholds
        $this->assertArrayHasKey('threshold', $processedConfig['halstead']);
        $this->assertEquals(5.0, $processedConfig['halstead']['threshold']['difficulty']);
        $this->assertEquals(3.0, $processedConfig['halstead']['threshold']['effort']);

        // Assertions for 'metrics'
        $this->assertArrayHasKey('lineCount', $processedConfig['metrics']);
        $this->assertEquals(60.0, $processedConfig['metrics']['lineCount']['threshold']);
        $this->assertEquals(25.0, $processedConfig['metrics']['lineCount']['scale']);
    }

    public function testEmptyConfig(): void
    {
        $configLoader = new ConfigLoader();
        $processor = new Processor();
        $treeBuilder = $configLoader->getConfigTreeBuilder();
        $configTree = $treeBuilder->buildTree();

        $processedConfig = $processor->process($configTree, []);

        $this->assertEmpty($processedConfig['excludePatterns']);
        $this->assertEmpty($processedConfig['cognitive']['metrics']);
        $this->assertEmpty($processedConfig['halstead']['threshold']);
        $this->assertEmpty($processedConfig['metrics']);
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
