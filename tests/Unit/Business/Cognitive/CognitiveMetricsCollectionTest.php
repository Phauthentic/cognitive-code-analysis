<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business\Cognitive;

use ArrayIterator;
use InvalidArgumentException;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollection;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class CognitiveMetricsCollectionTest extends TestCase
{
    public function testAddAndCount(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createMock(CognitiveMetrics::class);
        $metrics2 = $this->createMock(CognitiveMetrics::class);

        $this->assertSame(0, $metricsCollection->count());

        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);

        $this->assertSame(2, $metricsCollection->count());
    }

    public function testGetIterator(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createMock(CognitiveMetrics::class);
        $metrics2 = $this->createMock(CognitiveMetrics::class);

        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);

        $iterator = $metricsCollection->getIterator();

        $this->assertInstanceOf(ArrayIterator::class, $iterator);
        $this->assertCount(2, $iterator);
    }

    public function testFilter(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createMock(CognitiveMetrics::class);
        $metrics2 = $this->createMock(CognitiveMetrics::class);

        $metrics1->method('getScore')->willReturn(5.0);
        $metrics2->method('getScore')->willReturn(10.0);

        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);

        $filtered = $metricsCollection->filter(function (CognitiveMetrics $metric) {
            return $metric->getScore() > 7.0;
        });

        $this->assertCount(1, $filtered);
        $this->assertSame(10.0, $filtered->getIterator()[0]->getScore());
    }

    public function testFilterWithScoreGreaterThan(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createMock(CognitiveMetrics::class);
        $metrics2 = $this->createMock(CognitiveMetrics::class);

        $metrics1->method('getScore')->willReturn(5.0);
        $metrics2->method('getScore')->willReturn(10.0);

        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);

        $filtered = $metricsCollection->filterWithScoreGreaterThan(7.0);

        $this->assertCount(1, $filtered);
        $this->assertSame(10.0, $filtered->getIterator()[0]->getScore());
    }

    public function testContains(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createMock(CognitiveMetrics::class);
        $metrics2 = $this->createMock(\Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetrics::class);
        $metrics3 = $this->createMock(CognitiveMetrics::class);

        $metrics1->method('equals')->willReturn(false);
        $metrics2->method('equals')->willReturn(true);

        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);

        $this->assertTrue($metricsCollection->contains($metrics2));
        $this->assertFalse($metricsCollection->contains($metrics3));
    }

    public function testFilterByClassName(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createMock(CognitiveMetrics::class);
        $metrics2 = $this->createMock(CognitiveMetrics::class);

        $metrics1->method('getClass')->willReturn('ClassA');
        $metrics2->method('getClass')->willReturn('ClassB');

        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);

        $filtered = $metricsCollection->filterByClassName('ClassA');

        $this->assertCount(1, $filtered);
        $this->assertSame('ClassA', $filtered->getIterator()[0]->getClass());
    }

    public function testGroupBy(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createMock(CognitiveMetrics::class);
        $metrics2 = $this->createMock(CognitiveMetrics::class);
        $metrics3 = $this->createMock(\Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetrics::class);

        $metrics1->method('getClass')->willReturn('ClassA');
        $metrics2->method('getClass')->willReturn('ClassB');
        $metrics3->method('getClass')->willReturn('ClassA');

        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);
        $metricsCollection->add($metrics3);

        $grouped = $metricsCollection->groupBy('class');

        $this->assertCount(2, $grouped);
        $this->assertCount(2, $grouped['ClassA']);
        $this->assertCount(1, $grouped['ClassB']);
    }

    public function testGroupByThrowsExceptionOnInvalidProperty(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics = $this->createMock(CognitiveMetrics::class);
        $metricsCollection->add($metrics);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Property 'invalidProperty' does not exist in CognitiveMetrics class");

        $metricsCollection->groupBy('invalidProperty');
    }
}
