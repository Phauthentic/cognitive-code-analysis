<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnCalculator;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoverageReportReaderInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use PHPUnit\Framework\TestCase;

class ChurnCalculatorTest extends TestCase
{
    public function testCalculate(): void
    {
        $metric1 = $this->createMock(CognitiveMetrics::class);
        $metric1->method('getClass')->willReturn('ClassA');
        $metric1->method('getTimesChanged')->willReturn(5);
        $metric1->method('getScore')->willReturn(2.0);

        $metric2 = $this->createMock(CognitiveMetrics::class);
        $metric2->method('getClass')->willReturn('ClassB');
        $metric2->method('getTimesChanged')->willReturn(3);
        $metric2->method('getScore')->willReturn(4.0);

        $metricsCollection = $this->createMock(CognitiveMetricsCollection::class);
        $metricsCollection->method('getIterator')->willReturn(new \ArrayIterator([$metric1, $metric2]));

        $churnCalculator = new ChurnCalculator();
        $result = $churnCalculator->calculate($metricsCollection);

        $expected = [
            'ClassB' => [
                'timesChanged' => 3,
                'score' => 4.0,
                'churn' => 12.0,
                'file' => '',
                'coverage' => null,
                'riskChurn' => null,
                'riskLevel' => null,
            ],
            'ClassA' => [
                'timesChanged' => 5,
                'score' => 2.0,
                'churn' => 10.0,
                'file' => '',
                'coverage' => null,
                'riskChurn' => null,
                'riskLevel' => null,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testCalculateWithCoverage(): void
    {
        $metric1 = $this->createMock(CognitiveMetrics::class);
        $metric1->method('getClass')->willReturn('ClassA');
        $metric1->method('getTimesChanged')->willReturn(10);
        $metric1->method('getScore')->willReturn(3.0);
        $metric1->method('getFilename')->willReturn('ClassA.php');

        $metric2 = $this->createMock(CognitiveMetrics::class);
        $metric2->method('getClass')->willReturn('ClassB');
        $metric2->method('getTimesChanged')->willReturn(5);
        $metric2->method('getScore')->willReturn(8.0);
        $metric2->method('getFilename')->willReturn('ClassB.php');

        $metricsCollection = $this->createMock(CognitiveMetricsCollection::class);
        $metricsCollection->method('getIterator')->willReturn(new \ArrayIterator([$metric1, $metric2]));

        $coverageReader = $this->createMock(CoverageReportReaderInterface::class);
        $coverageReader->method('getLineCoverage')
            ->willReturnMap([
                ['ClassA', 0.9],  // 90% coverage
                ['ClassB', 0.2],  // 20% coverage
            ]);

        $churnCalculator = new ChurnCalculator();
        $result = $churnCalculator->calculate($metricsCollection, $coverageReader);

        // ClassA: churn=30, coverage=0.9, riskChurn=30*(1-0.9)=3
        // ClassB: churn=40, coverage=0.2, riskChurn=40*(1-0.2)=32

        $this->assertEquals(40.0, $result['ClassB']['churn']);
        $this->assertEqualsWithDelta(32.0, $result['ClassB']['riskChurn'], 0.01);
        $this->assertEquals(0.2, $result['ClassB']['coverage']);
        $this->assertEquals('CRITICAL', $result['ClassB']['riskLevel']); // churn>30 && coverage<0.5

        $this->assertEquals(30.0, $result['ClassA']['churn']);
        $this->assertEqualsWithDelta(3.0, $result['ClassA']['riskChurn'], 0.01);
        $this->assertEquals(0.9, $result['ClassA']['coverage']);
        $this->assertEquals('LOW', $result['ClassA']['riskLevel']);
    }

    public function testCalculateRiskLevels(): void
    {
        // Test CRITICAL: churn > 30 AND coverage < 0.5
        $metricCritical = $this->createMock(CognitiveMetrics::class);
        $metricCritical->method('getClass')->willReturn('CriticalClass');
        $metricCritical->method('getTimesChanged')->willReturn(10);
        $metricCritical->method('getScore')->willReturn(4.0); // churn = 40
        $metricCritical->method('getFilename')->willReturn('CriticalClass.php');

        // Test HIGH: churn > 20 AND coverage < 0.7
        $metricHigh = $this->createMock(CognitiveMetrics::class);
        $metricHigh->method('getClass')->willReturn('HighClass');
        $metricHigh->method('getTimesChanged')->willReturn(5);
        $metricHigh->method('getScore')->willReturn(5.0); // churn = 25
        $metricHigh->method('getFilename')->willReturn('HighClass.php');

        // Test MEDIUM: churn > 10 AND coverage < 0.8
        $metricMedium = $this->createMock(CognitiveMetrics::class);
        $metricMedium->method('getClass')->willReturn('MediumClass');
        $metricMedium->method('getTimesChanged')->willReturn(3);
        $metricMedium->method('getScore')->willReturn(4.0); // churn = 12
        $metricMedium->method('getFilename')->willReturn('MediumClass.php');

        // Test LOW
        $metricLow = $this->createMock(CognitiveMetrics::class);
        $metricLow->method('getClass')->willReturn('LowClass');
        $metricLow->method('getTimesChanged')->willReturn(2);
        $metricLow->method('getScore')->willReturn(3.0); // churn = 6
        $metricLow->method('getFilename')->willReturn('LowClass.php');

        $metricsCollection = $this->createMock(CognitiveMetricsCollection::class);
        $metricsCollection->method('getIterator')->willReturn(
            new \ArrayIterator([$metricCritical, $metricHigh, $metricMedium, $metricLow])
        );

        $coverageReader = $this->createMock(CoverageReportReaderInterface::class);
        $coverageReader->method('getLineCoverage')
            ->willReturnMap([
                ['CriticalClass', 0.3],  // 30% coverage
                ['HighClass', 0.6],      // 60% coverage
                ['MediumClass', 0.75],   // 75% coverage
                ['LowClass', 0.95],      // 95% coverage
            ]);

        $churnCalculator = new ChurnCalculator();
        $result = $churnCalculator->calculate($metricsCollection, $coverageReader);

        $this->assertEquals('CRITICAL', $result['CriticalClass']['riskLevel']);
        $this->assertEquals('HIGH', $result['HighClass']['riskLevel']);
        $this->assertEquals('MEDIUM', $result['MediumClass']['riskLevel']);
        $this->assertEquals('LOW', $result['LowClass']['riskLevel']);
    }

    public function testCalculateWithNoCoverageForClass(): void
    {
        $metric = $this->createMock(CognitiveMetrics::class);
        $metric->method('getClass')->willReturn('ClassA');
        $metric->method('getTimesChanged')->willReturn(5);
        $metric->method('getScore')->willReturn(2.0);
        $metric->method('getFilename')->willReturn('ClassA.php');

        $metricsCollection = $this->createMock(CognitiveMetricsCollection::class);
        $metricsCollection->method('getIterator')->willReturn(new \ArrayIterator([$metric]));

        $coverageReader = $this->createMock(CoverageReportReaderInterface::class);
        $coverageReader->method('getLineCoverage')->willReturn(null);

        $churnCalculator = new ChurnCalculator();
        $result = $churnCalculator->calculate($metricsCollection, $coverageReader);

        // When coverage is null, assume 0.0 coverage
        $this->assertEquals(0.0, $result['ClassA']['coverage']);
        $this->assertEquals(10.0, $result['ClassA']['riskChurn']); // 5 * 2.0 * (1 - 0.0)
    }
}
