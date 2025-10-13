<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\ClassMetricsStatistics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRenderer;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use Phauthentic\CognitiveCodeAnalysis\Config\MetricsConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class CognitiveMetricTextRendererTest extends TestCase
{
    private CognitiveMetricTextRenderer $renderer;
    private ConfigService $configService;
    private ClassMetricsStatistics $statistics;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->configService = $this->createMock(ConfigService::class);
        $this->statistics = $this->createMock(ClassMetricsStatistics::class);
        $this->output = new BufferedOutput();
        
        $this->renderer = new CognitiveMetricTextRenderer(
            $this->configService,
            $this->statistics
        );
    }

    #[Test]
    public function testRenderWithThresholdEnabledShowsStatistics(): void
    {
        $config = $this->createConfig(true, 1.0, true);
        $this->configService->method('getConfig')->willReturn($config);

        $collection = $this->createMetricsCollection();
        
        // Mock statistics calculations
        $this->statistics->method('calculateOverallStatistics')
            ->willReturn([
                'totalClasses' => 2,
                'classesExceedingThreshold' => 1,
                'percentageExceedingThreshold' => 50.0
            ]);

        $this->renderer->render($collection, $this->output);
        
        $output = $this->output->fetch();
        
        // Check that statistics are displayed
        $this->assertStringContainsString('Methods exceeding threshold:', $output);
        $this->assertStringContainsString('Average class score:', $output);
        $this->assertStringContainsString('Overall Statistics:', $output);
        $this->assertStringContainsString('Classes with methods exceeding threshold: 1 of 2 (50%)', $output);
    }

    #[Test]
    public function testRenderWithThresholdDisabledDoesNotShowStatistics(): void
    {
        $config = $this->createConfig(false, 1.0, true);
        $this->configService->method('getConfig')->willReturn($config);

        $collection = $this->createMetricsCollection();
        
        $this->renderer->render($collection, $this->output);
        
        $output = $this->output->fetch();
        
        // Check that statistics are NOT displayed
        $this->assertStringNotContainsString('Methods exceeding threshold:', $output);
        $this->assertStringNotContainsString('Average class score:', $output);
        $this->assertStringNotContainsString('Overall Statistics:', $output);
    }

    #[Test]
    public function testRenderWithSingleTableLayoutDoesNotShowOverallStatistics(): void
    {
        $config = $this->createConfig(true, 1.0, false); // groupByClass = false
        $this->configService->method('getConfig')->willReturn($config);

        $collection = $this->createMetricsCollection();
        
        $this->renderer->render($collection, $this->output);
        
        $output = $this->output->fetch();
        
        // Check that overall statistics are NOT displayed in single table mode
        $this->assertStringNotContainsString('Overall Statistics:', $output);
        $this->assertStringNotContainsString('Classes with methods exceeding threshold:', $output);
    }

    #[Test]
    public function testRenderShowsCorrectPercentageCalculation(): void
    {
        $config = $this->createConfig(true, 1.0, true);
        $this->configService->method('getConfig')->willReturn($config);

        // Create a collection with 3 methods, 2 exceeding threshold
        $collection = new CognitiveMetricsCollection();
        
        $metric1 = $this->createMetric('TestClass', 'method1', 0.5); // Below threshold
        $metric2 = $this->createMetric('TestClass', 'method2', 1.5); // Above threshold
        $metric3 = $this->createMetric('TestClass', 'method3', 2.0); // Above threshold
        
        $collection->add($metric1);
        $collection->add($metric2);
        $collection->add($metric3);

        // Mock statistics calculations for overall statistics
        $this->statistics->method('calculateOverallStatistics')
            ->willReturn([
                'totalClasses' => 1,
                'classesExceedingThreshold' => 1,
                'percentageExceedingThreshold' => 100.0
            ]);

        $this->renderer->render($collection, $this->output);
        
        $output = $this->output->fetch();
        
        // Should show 2 of 3 methods (66.7%) exceeding threshold
        $this->assertStringContainsString('Methods exceeding threshold: 2 of 3 (66.7%)', $output);
    }

    #[Test]
    public function testRenderShowsCorrectAverageScore(): void
    {
        $config = $this->createConfig(true, 1.0, true);
        $this->configService->method('getConfig')->willReturn($config);

        // Create a collection with specific scores
        $collection = new CognitiveMetricsCollection();
        
        $metric1 = $this->createMetric('TestClass', 'method1', 1.0);
        $metric2 = $this->createMetric('TestClass', 'method2', 2.0);
        $metric3 = $this->createMetric('TestClass', 'method3', 3.0);
        
        $collection->add($metric1);
        $collection->add($metric2);
        $collection->add($metric3);

        // Mock statistics calculations for overall statistics
        $this->statistics->method('calculateOverallStatistics')
            ->willReturn([
                'totalClasses' => 1,
                'classesExceedingThreshold' => 1,
                'percentageExceedingThreshold' => 100.0
            ]);

        $this->renderer->render($collection, $this->output);
        
        $output = $this->output->fetch();
        
        // Should show average score of 2.0
        $this->assertStringContainsString('Average class score: 2', $output);
    }

    private function createConfig(bool $showOnlyMethodsExceedingThreshold, float $scoreThreshold, bool $groupByClass): CognitiveConfig
    {
        return new CognitiveConfig(
            excludeFilePatterns: [],
            excludePatterns: [],
            metrics: [
                'lineCount' => new MetricsConfig(60, 25.0, true),
                'argCount' => new MetricsConfig(4, 1.0, true),
                'returnCount' => new MetricsConfig(2, 5.0, true),
                'variableCount' => new MetricsConfig(2, 5.0, true),
                'propertyCallCount' => new MetricsConfig(2, 15.0, true),
                'ifCount' => new MetricsConfig(3, 1.0, true),
                'ifNestingLevel' => new MetricsConfig(1, 1.0, true),
                'elseCount' => new MetricsConfig(1, 1.0, true),
            ],
            showOnlyMethodsExceedingThreshold: $showOnlyMethodsExceedingThreshold,
            scoreThreshold: $scoreThreshold,
            showHalsteadComplexity: false,
            showCyclomaticComplexity: false,
            groupByClass: $groupByClass,
            showDetailedCognitiveMetrics: true,
            customExporters: []
        );
    }

    private function createMetricsCollection(): CognitiveMetricsCollection
    {
        $collection = new CognitiveMetricsCollection();
        
        $metric1 = $this->createMetric('TestClass1', 'method1', 0.5);
        $metric2 = $this->createMetric('TestClass1', 'method2', 1.5);
        $metric3 = $this->createMetric('TestClass2', 'method1', 0.8);
        
        $collection->add($metric1);
        $collection->add($metric2);
        $collection->add($metric3);
        
        return $collection;
    }

    private function createMetric(string $class, string $method, float $score): CognitiveMetrics
    {
        $metric = new CognitiveMetrics([
            'class' => $class,
            'method' => $method,
            'file' => 'test.php',
            'line' => 1,
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0,
        ]);
        
        $metric->setScore($score);
        
        return $metric;
    }
}
