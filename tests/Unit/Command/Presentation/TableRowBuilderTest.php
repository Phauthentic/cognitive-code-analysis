<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\MetricFormatter;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\TableRowBuilder;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TableRowBuilderTest extends TestCase
{
    #[Test]
    public function decreaseDeltaRendersSingleMinusSign(): void
    {
        $before = $this->metricsWithLineCountWeight(5.0);
        $after = $this->metricsWithLineCountWeight(3.0);
        $after->calculateDeltas($before);

        $cell = $this->buildRow($after)['lineCount'];

        $this->assertStringContainsString('<info>Δ -2</info>', $cell);
        $this->assertStringNotContainsString('--', $cell);
    }

    #[Test]
    public function increaseDeltaRendersPlusSign(): void
    {
        $before = $this->metricsWithLineCountWeight(3.0);
        $after = $this->metricsWithLineCountWeight(5.0);
        $after->calculateDeltas($before);

        $cell = $this->buildRow($after)['lineCount'];

        $this->assertStringContainsString('<error>Δ +2</error>', $cell);
    }

    private function buildRow(CognitiveMetrics $metrics): array
    {
        $config = new CognitiveConfig(
            excludeFilePatterns: [],
            excludePatterns: [],
            metrics: [],
            showOnlyMethodsExceedingThreshold: false,
            scoreThreshold: 0.0,
        );

        $builder = new TableRowBuilder(new MetricFormatter($config), $config, false);

        return $builder->buildRowWithClassInfo($metrics);
    }

    private function metricsWithLineCountWeight(float $weight): CognitiveMetrics
    {
        return new CognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'testMethod',
            'file' => 'TestClass.php',
            'line' => 1,
            'lineCount' => 10,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0,
            'lineCountWeight' => $weight,
        ]);
    }
}
