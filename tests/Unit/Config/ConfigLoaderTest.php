<?php

declare(strict_types=1);

namespace Phauthentic\CodeQuality\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Phauthentic\CodeQuality\Config\ConfigLoader;

/**
 *
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
            'metrics' => [
                'lineCount' => [
                    'threshold' => 60.0,
                    'scale' => 2.0,
                ],
                'argCount' => [
                    'threshold' => 4.0,
                    'scale' => 1.0,
                ],
            ],
        ];

        $processedConfig = $processor->process($configTree, [$config]);

        $this->assertArrayHasKey('excludePatterns', $processedConfig);
        $this->assertArrayHasKey('metrics', $processedConfig);
        $this->assertArrayHasKey('lineCount', $processedConfig['metrics']);
        $this->assertArrayHasKey('threshold', $processedConfig['metrics']['lineCount']);
        $this->assertEquals(60.0, $processedConfig['metrics']['lineCount']['threshold']);
        $this->assertEquals(2.0, $processedConfig['metrics']['lineCount']['scale']);

        $this->assertArrayHasKey('argCount', $processedConfig['metrics']);
        $this->assertArrayHasKey('threshold', $processedConfig['metrics']['argCount']);
        $this->assertEquals(4.0, $processedConfig['metrics']['argCount']['threshold']);
        $this->assertEquals(1.0, $processedConfig['metrics']['argCount']['scale']);
    }

    public function testEmptyConfig(): void
    {
        $configLoader = new ConfigLoader();
        $processor = new Processor();
        $treeBuilder = $configLoader->getConfigTreeBuilder();
        $configTree = $treeBuilder->buildTree();

        $processedConfig = $processor->process($configTree, []);

        $this->assertEmpty($processedConfig['excludePatterns']);
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
