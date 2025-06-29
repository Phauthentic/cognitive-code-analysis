<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnCalculator;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use PHPUnit\Framework\TestCase;

/**
 *
 */
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
            'ClassA' => ['timesChanged' => 5, 'score' => 2.0, 'churn' => 10.0, 'file' => ''],
            'ClassB' => ['timesChanged' => 3, 'score' => 4.0, 'churn' => 12.0, 'file' => ''],
        ];

        $this->assertEquals($expected, $result);
    }
}
