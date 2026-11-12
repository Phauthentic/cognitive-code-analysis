<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\ScoreCalculator;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigLoader;
use Phauthentic\CognitiveCodeAnalysis\Config\MetricsConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ScoreCalculatorTest extends TestCase
{
    private ScoreCalculator $scoreCalculator;
    private CognitiveMetrics $metrics;

    protected function setUp(): void
    {
        $this->scoreCalculator = new ScoreCalculator();
        $this->metrics = new CognitiveMetrics([
            'class' => 'Test',
            'method' => 'test',
            'file' => 'test.php',
            'lineCount' => 10,
            'argCount' => 5,
            'returnCount' => 2,
            'variableCount' => 3,
            'propertyCallCount' => 2,
            'ifCount' => 3,
            'ifNestingLevel' => 2,
            'elseCount' => 2,
        ]);
    }

    #[Test]
    public function testCalculate(): void
    {
        $config = (new ConfigService(
            new Processor(),
            new ConfigLoader()
        ))->getConfig();

        $this->scoreCalculator->calculate($this->metrics, $config);

        // Assert the final score
        $this->assertGreaterThan(0, $this->metrics->getScore());

        // Assert the score is a float
        $this->assertIsFloat($this->metrics->getScore());
    }

    #[Test]
    public function testDisabledMetricHasZeroWeightAndDoesNotCountTowardScore(): void
    {
        $service = new ConfigService(new Processor(), new ConfigLoader());
        $enabled = $service->getConfig();
        $disabled = $service->getConfig();
        $metrics = $disabled->metrics;
        $metrics['lineCount'] = new MetricsConfig(
            $metrics['lineCount']->threshold,
            $metrics['lineCount']->scale,
            false
        );
        $disabled = new CognitiveConfig(
            excludeFilePatterns: $disabled->excludeFilePatterns,
            excludePatterns: $disabled->excludePatterns,
            metrics: $metrics,
            showOnlyMethodsExceedingThreshold: $disabled->showOnlyMethodsExceedingThreshold,
            scoreThreshold: $disabled->scoreThreshold,
        );

        $longMethod = [
            'class' => 'T',
            'method' => 'm',
            'file' => 't.php',
            'lineCount' => 500,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0,
        ];
        $on = new CognitiveMetrics($longMethod);
        $off = new CognitiveMetrics($longMethod);

        $this->scoreCalculator->calculate($on, $enabled);
        $this->scoreCalculator->calculate($off, $disabled);

        $this->assertGreaterThan(0, $on->getLineCountWeight());
        $this->assertSame(0.0, $off->getLineCountWeight());
        $this->assertSame(0.0, $off->getScore());
        $this->assertSame(500, $off->getLineCount());
    }
}
