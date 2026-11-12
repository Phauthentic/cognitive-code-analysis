<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
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
            ]
        ];

        $processedConfig = $processor->process($configTree, [$config]);

        $this->assertArrayHasKey('cognitive', $processedConfig);
        $this->assertArrayHasKey('lineCount', $processedConfig['cognitive']['metrics']);
        $this->assertEquals(60.0, $processedConfig['cognitive']['metrics']['lineCount']['threshold']);
        $this->assertEquals(25.0, $processedConfig['cognitive']['metrics']['lineCount']['scale']);
    }

    public function testEmptyConfig(): void
    {
        $configLoader = new ConfigLoader();
        $processor = new Processor();
        $treeBuilder = $configLoader->getConfigTreeBuilder();
        $configTree = $treeBuilder->buildTree();

        $processedConfig = $processor->process($configTree, []);

        $this->assertEmpty($processedConfig['cognitive']['metrics']);
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

    public function testPartialMetricsBlockFallsBackToBundledThresholds(): void
    {
        $config = $this->process(['cognitive' => ['metrics' => ['lineCount' => ['threshold' => 10]]]]);

        $this->assertEquals(10, $config['cognitive']['metrics']['lineCount']['threshold']);
        $this->assertEquals(4, $config['cognitive']['metrics']['variableCount']['threshold']);
        $this->assertEquals(4, $config['cognitive']['metrics']['propertyCallCount']['threshold']);
    }

    public function testCodeDefaultsMatchBundledConfig(): void
    {
        $bundled = Yaml::parseFile(__DIR__ . '/../../../phpcca.yaml')['cognitive'];
        // Blocks are only populated when present, so pass them empty to expose their leaf defaults.
        $defaults = $this->process(['cognitive' => ['cache' => [], 'performance' => []]])['cognitive'];

        $this->assertEquals(
            (new ConfigLoader())->getCognitiveMetricDefaults(),
            $bundled['metrics']
        );

        // Keys with no value in the bundled file are null there, empty by default in code.
        $bundled['excludePatterns'] ??= [];
        $bundled['excludeFilePatterns'] ??= [];
        unset($bundled['metrics']);
        foreach ($bundled as $key => $value) {
            $this->assertEquals($value, $defaults[$key], "Default for '$key' differs from phpcca.yaml");
        }
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        return (new Processor())->process((new ConfigLoader())->getConfigTreeBuilder()->buildTree(), [$config]);
    }
}
