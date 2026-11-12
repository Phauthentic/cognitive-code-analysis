<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\MetricFormatter;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\TableHeaderBuilder;
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

    #[Test]
    public function bothRowBuildersRenderHalsteadAndCyclomaticDeltas(): void
    {
        $config = new CognitiveConfig(
            excludeFilePatterns: [],
            excludePatterns: [],
            metrics: [],
            showOnlyMethodsExceedingThreshold: false,
            scoreThreshold: 0.5,
            showHalsteadComplexity: true,
            showCyclomaticComplexity: true,
            showDetailedCognitiveMetrics: false,
        );
        $builder = new TableRowBuilder(new MetricFormatter($config), $config);

        $metrics = $this->createMetrics(100.0, 10.0, 1000.0, 5);
        $metrics->calculateDeltas($this->createMetrics(50.0, 5.0, 500.0, 2));

        $grouped = $builder->buildRow($metrics);
        $single = $builder->buildRowWithClassInfo($metrics);

        $this->assertStringContainsString('Δ +50', (string) $grouped['halsteadVolume']);
        $this->assertStringContainsString('Δ +3', (string) $grouped['cyclomaticComplexity']);
        $this->assertSame('Foo', $single['className']);
        unset($single['className']);
        $this->assertSame($grouped, $single);

        $headers = new TableHeaderBuilder($config);
        $this->assertCount(count($headers->getGroupedTableHeaders()), $grouped);
        $this->assertCount(count($headers->getSingleTableHeaders()), $single + ['className' => 'Foo']);
    }

    /**
     * @return array<string, mixed>
     */
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

    private function createMetrics(float $volume, float $difficulty, float $effort, int $complexity): CognitiveMetrics
    {
        return new CognitiveMetrics([
            'class' => 'Foo',
            'method' => 'bar',
            'file' => 'Foo.php',
            'line' => 1,
            'lineCount' => 1, 'argCount' => 0, 'returnCount' => 0, 'variableCount' => 0,
            'propertyCallCount' => 0, 'ifCount' => 0, 'ifNestingLevel' => 0, 'elseCount' => 0,
            'halsteadVolume' => $volume,
            'halsteadDifficulty' => $difficulty,
            'halsteadEffort' => $effort,
            'cyclomaticComplexity' => $complexity,
        ]);
    }
}
