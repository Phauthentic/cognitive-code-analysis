<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Config;

use Phauthentic\CognitiveCodeAnalysis\Config\ConfigFactory;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigLoader;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * Test case for custom exporters configuration.
 */
class CustomExportersConfigTest extends TestCase
{
    #[Test]
    public function testCustomExportersConfiguration(): void
    {
        $configLoader = new ConfigLoader();
        $processor = new Processor();
        $treeBuilder = $configLoader->getConfigTreeBuilder();
        $configTree = $treeBuilder->buildTree();

        $config = [
            'cognitive' => [
                'excludeFilePatterns' => [],
                'excludePatterns' => [],
                'scoreThreshold' => 0.5,
                'showOnlyMethodsExceedingThreshold' => false,
                'showHalsteadComplexity' => false,
                'showCyclomaticComplexity' => false,
                'showDetailedCognitiveMetrics' => true,
                'groupByClass' => true,
                'metrics' => [
                    'lineCount' => [
                        'threshold' => 60,
                        'scale' => 25.0,
                        'enabled' => true
                    ]
                ],
                'customReporters' => [
                    'cognitive' => [
                        'pdf' => [
                            'class' => 'My\Custom\PdfExporter',
                            'file' => '/path/to/PdfExporter.php',
                        ],
                        'xml' => [
                            'class' => 'My\Custom\XmlExporter',
                            'file' => null,
                        ]
                    ],
                    'churn' => [
                        'custom' => [
                            'class' => 'My\Custom\ChurnExporter',
                            'file' => '/path/to/ChurnExporter.php'
                        ]
                    ]
                ]
            ]
        ];

        $processedConfig = $processor->process($configTree, [$config]);

        $this->assertArrayHasKey('cognitive', $processedConfig);
        $this->assertArrayHasKey('customReporters', $processedConfig['cognitive']);
        $this->assertArrayHasKey('cognitive', $processedConfig['cognitive']['customReporters']);
        $this->assertArrayHasKey('churn', $processedConfig['cognitive']['customReporters']);

        // Test cognitive exporters
        $cognitiveExporters = $processedConfig['cognitive']['customReporters']['cognitive'];
        $this->assertArrayHasKey('pdf', $cognitiveExporters);
        $this->assertArrayHasKey('xml', $cognitiveExporters);

        $this->assertEquals('My\Custom\PdfExporter', $cognitiveExporters['pdf']['class']);
        $this->assertEquals('/path/to/PdfExporter.php', $cognitiveExporters['pdf']['file']);

        $this->assertEquals('My\Custom\XmlExporter', $cognitiveExporters['xml']['class']);
        $this->assertNull($cognitiveExporters['xml']['file']);

        // Test churn exporters
        $churnExporters = $processedConfig['cognitive']['customReporters']['churn'];
        $this->assertArrayHasKey('custom', $churnExporters);
        $this->assertEquals('My\Custom\ChurnExporter', $churnExporters['custom']['class']);
        $this->assertEquals('/path/to/ChurnExporter.php', $churnExporters['custom']['file']);
    }

    #[Test]
    public function testCustomExportersWithDefaults(): void
    {
        $configLoader = new ConfigLoader();
        $processor = new Processor();
        $treeBuilder = $configLoader->getConfigTreeBuilder();
        $configTree = $treeBuilder->buildTree();

        $config = [
            'cognitive' => [
                'excludeFilePatterns' => [],
                'excludePatterns' => [],
                'scoreThreshold' => 0.5,
                'showOnlyMethodsExceedingThreshold' => false,
                'showHalsteadComplexity' => false,
                'showCyclomaticComplexity' => false,
                'showDetailedCognitiveMetrics' => true,
                'groupByClass' => true,
                'metrics' => [
                    'lineCount' => [
                        'threshold' => 60,
                        'scale' => 25.0,
                        'enabled' => true
                    ]
                ],
                'customReporters' => [
                    'cognitive' => [
                        'minimal' => [
                            'class' => 'My\Custom\MinimalExporter'
                            // file should default to null
                        ]
                    ]
                ]
            ]
        ];

        $processedConfig = $processor->process($configTree, [$config]);

        $cognitiveExporters = $processedConfig['cognitive']['customReporters']['cognitive'];
        $this->assertArrayHasKey('minimal', $cognitiveExporters);
        $this->assertEquals('My\Custom\MinimalExporter', $cognitiveExporters['minimal']['class']);
        $this->assertNull($cognitiveExporters['minimal']['file']);
    }

    #[Test]
    public function testEmptyCustomExporters(): void
    {
        $configLoader = new ConfigLoader();
        $processor = new Processor();
        $treeBuilder = $configLoader->getConfigTreeBuilder();
        $configTree = $treeBuilder->buildTree();

        $config = [
            'cognitive' => [
                'excludeFilePatterns' => [],
                'excludePatterns' => [],
                'scoreThreshold' => 0.5,
                'showOnlyMethodsExceedingThreshold' => false,
                'showHalsteadComplexity' => false,
                'showCyclomaticComplexity' => false,
                'showDetailedCognitiveMetrics' => true,
                'groupByClass' => true,
                'metrics' => [
                    'lineCount' => [
                        'threshold' => 60,
                        'scale' => 25.0,
                        'enabled' => true
                    ]
                ]
                // No customReporters section
            ]
        ];

        $processedConfig = $processor->process($configTree, [$config]);

        $this->assertArrayHasKey('cognitive', $processedConfig);

        // customReporters might not be present if not provided
        if (!isset($processedConfig['cognitive']['customReporters'])) {
            return;
        }

        $this->assertArrayHasKey('cognitive', $processedConfig['cognitive']['customReporters']);
        $this->assertArrayHasKey('churn', $processedConfig['cognitive']['customReporters']);
        $this->assertEmpty($processedConfig['cognitive']['customReporters']['cognitive']);
        $this->assertEmpty($processedConfig['cognitive']['customReporters']['churn']);
    }

    #[Test]
    public function testConfigFactoryWithCustomExporters(): void
    {
        $config = [
            'cognitive' => [
                'excludeFilePatterns' => [],
                'excludePatterns' => [],
                'scoreThreshold' => 0.5,
                'showOnlyMethodsExceedingThreshold' => false,
                'showHalsteadComplexity' => false,
                'showCyclomaticComplexity' => false,
                'showDetailedCognitiveMetrics' => true,
                'groupByClass' => true,
                'metrics' => [
                    'lineCount' => [
                        'threshold' => 60,
                        'scale' => 25.0,
                        'enabled' => true
                    ]
                ],
                'customReporters' => [
                    'cognitive' => [
                        'test' => [
                            'class' => 'Test\Exporter',
                            'file' => '/test/file.php',
                        ]
                    ],
                    'churn' => [
                        'test' => [
                            'class' => 'Test\ChurnExporter',
                            'file' => null
                        ]
                    ]
                ]
            ]
        ];

        $configFactory = new ConfigFactory();
        $cognitiveConfig = $configFactory->fromArray($config);

        $this->assertInstanceOf(CognitiveConfig::class, $cognitiveConfig);
        $this->assertArrayHasKey('cognitive', $cognitiveConfig->customReporters);
        $this->assertArrayHasKey('churn', $cognitiveConfig->customReporters);

        $cognitiveExporters = $cognitiveConfig->customReporters['cognitive'];
        $this->assertArrayHasKey('test', $cognitiveExporters);
        $this->assertEquals('Test\Exporter', $cognitiveExporters['test']['class']);
        $this->assertEquals('/test/file.php', $cognitiveExporters['test']['file']);

        $churnExporters = $cognitiveConfig->customReporters['churn'];
        $this->assertArrayHasKey('test', $churnExporters);
        $this->assertEquals('Test\ChurnExporter', $churnExporters['test']['class']);
        $this->assertNull($churnExporters['test']['file']);
    }

    #[Test]
    public function testConfigFactoryWithoutCustomExporters(): void
    {
        $config = [
            'cognitive' => [
                'excludeFilePatterns' => [],
                'excludePatterns' => [],
                'scoreThreshold' => 0.5,
                'showOnlyMethodsExceedingThreshold' => false,
                'showHalsteadComplexity' => false,
                'showCyclomaticComplexity' => false,
                'showDetailedCognitiveMetrics' => true,
                'groupByClass' => true,
                'metrics' => [
                    'lineCount' => [
                        'threshold' => 60,
                        'scale' => 25.0,
                        'enabled' => true
                    ]
                ]
                // No customReporters section
            ]
        ];

        $configFactory = new ConfigFactory();
        $cognitiveConfig = $configFactory->fromArray($config);

        $this->assertInstanceOf(CognitiveConfig::class, $cognitiveConfig);
        $this->assertEmpty($cognitiveConfig->customReporters);
    }

    #[Test]
    public function testInvalidCustomExporterConfiguration(): void
    {
        $configLoader = new ConfigLoader();
        $processor = new Processor();
        $treeBuilder = $configLoader->getConfigTreeBuilder();
        $configTree = $treeBuilder->buildTree();

        $config = [
            'cognitive' => [
                'excludeFilePatterns' => [],
                'excludePatterns' => [],
                'scoreThreshold' => 0.5,
                'showOnlyMethodsExceedingThreshold' => false,
                'showHalsteadComplexity' => false,
                'showCyclomaticComplexity' => false,
                'showDetailedCognitiveMetrics' => true,
                'groupByClass' => true,
                'metrics' => [
                    'lineCount' => [
                        'threshold' => 60,
                        'scale' => 25.0,
                        'enabled' => true
                    ]
                ],
                'customReporters' => [
                    'cognitive' => [
                        'invalid' => [
                            // Missing required 'class' field
                            'file' => '/test/file.php',
                        ]
                    ]
                ]
            ]
        ];

        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        $processor->process($configTree, [$config]);
    }
}
