<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use ArrayIterator;
use InvalidArgumentException;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CognitiveMetricsCollection
 */
class CognitiveMetricsCollectionTest extends TestCase
{
    private function createCognitiveMetrics(array $data): CognitiveMetrics
    {
        return CognitiveMetrics::fromArray($data);
    }

    public function testAddAndCount(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createCognitiveMetrics([
            'class' => 'ClassA',
            'method' => 'methodA',
            'lineCount' => 10,
            'argCount' => 2,
            'returnCount' => 1,
            'variableCount' => 3,
            'propertyCallCount' => 4,
            'ifCount' => 2,
            'ifNestingLevel' => 1,
            'elseCount' => 0
        ]);

        $metrics2 = $this->createCognitiveMetrics([
            'class' => 'ClassB',
            'method' => 'methodB',
            'lineCount' => 20,
            'argCount' => 4,
            'returnCount' => 2,
            'variableCount' => 5,
            'propertyCallCount' => 3,
            'ifCount' => 3,
            'ifNestingLevel' => 2,
            'elseCount' => 1
        ]);

        $this->assertSame(0, $metricsCollection->count());

        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);

        $this->assertSame(2, $metricsCollection->count());
    }

    public function testGetIterator(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createCognitiveMetrics([
            'class' => 'ClassA',
            'method' => 'methodA',
            'lineCount' => 10,
            'argCount' => 2,
            'returnCount' => 1,
            'variableCount' => 3,
            'propertyCallCount' => 4,
            'ifCount' => 2,
            'ifNestingLevel' => 1,
            'elseCount' => 0
        ]);

        $metrics2 = $this->createCognitiveMetrics([
            'class' => 'ClassB',
            'method' => 'methodB',
            'lineCount' => 20,
            'argCount' => 4,
            'returnCount' => 2,
            'variableCount' => 5,
            'propertyCallCount' => 3,
            'ifCount' => 3,
            'ifNestingLevel' => 2,
            'elseCount' => 1
        ]);

        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);

        $iterator = $metricsCollection->getIterator();

        $this->assertInstanceOf(ArrayIterator::class, $iterator);
        $this->assertCount(2, $iterator);
    }

    public function testFilter(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createCognitiveMetrics([
            'class' => 'ClassA',
            'method' => 'methodA',
            'lineCount' => 10,
            'argCount' => 2,
            'returnCount' => 1,
            'variableCount' => 3,
            'propertyCallCount' => 4,
            'ifCount' => 2,
            'ifNestingLevel' => 1,
            'elseCount' => 0,
            'score' => 5.0
        ]);
        $metrics1->setScore(5.0);

        $metrics2 = $this->createCognitiveMetrics([
            'class' => 'ClassB',
            'method' => 'methodB',
            'lineCount' => 20,
            'argCount' => 4,
            'returnCount' => 2,
            'variableCount' => 5,
            'propertyCallCount' => 3,
            'ifCount' => 3,
            'ifNestingLevel' => 2,
            'elseCount' => 1,
            'score' => 10.0
        ]);
        $metrics2->setScore(10.0);

        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);

        $filtered = $metricsCollection->filter(function (CognitiveMetrics $metric) {
            return $metric->getScore() > 7.0;
        });

        $this->assertCount(1, $filtered);
        $this->assertSame(10.0, $filtered->getIterator()['ClassB::methodB']->getScore());
    }

    public function testContains(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createCognitiveMetrics([
            'class' => 'ClassA',
            'method' => 'methodA',
            'lineCount' => 10,
            'argCount' => 2,
            'returnCount' => 1,
            'variableCount' => 3,
            'propertyCallCount' => 4,
            'ifCount' => 2,
            'ifNestingLevel' => 1,
            'elseCount' => 0
        ]);

        $metrics2 = $this->createCognitiveMetrics([
            'class' => 'ClassB',
            'method' => 'methodB',
            'lineCount' => 20,
            'argCount' => 4,
            'returnCount' => 2,
            'variableCount' => 5,
            'propertyCallCount' => 3,
            'ifCount' => 3,
            'ifNestingLevel' => 2,
            'elseCount' => 1
        ]);

        $metrics3 = $this->createCognitiveMetrics([
            'class' => 'ClassC',
            'method' => 'methodC',
            'lineCount' => 30,
            'argCount' => 6,
            'returnCount' => 3,
            'variableCount' => 8,
            'propertyCallCount' => 7,
            'ifCount' => 4,
            'ifNestingLevel' => 3,
            'elseCount' => 2
        ]);

        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);

        $this->assertTrue($metricsCollection->contains($metrics2));
        $this->assertFalse($metricsCollection->contains($metrics3));
    }
}
