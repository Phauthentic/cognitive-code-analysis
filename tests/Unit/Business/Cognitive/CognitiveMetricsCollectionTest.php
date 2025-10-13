<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use ArrayIterator;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function testAddAndCount(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createCognitiveMetrics([
            'class' => 'ClassA',
            'method' => 'methodA',
            'file' => 'ClassA.php',
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
            'file' => 'ClassB.php',
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

    #[Test]
    public function testGetIterator(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createCognitiveMetrics([
            'class' => 'ClassA',
            'method' => 'methodA',
            'file' => 'ClassA.php',
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
            'file' => 'ClassB.php',
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

    #[Test]
    public function testFilter(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createCognitiveMetrics([
            'class' => 'ClassA',
            'method' => 'methodA',
            'file' => 'ClassA.php',
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
            'file' => 'ClassB.php',
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

    #[Test]
    public function testContains(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = $this->createCognitiveMetrics([
            'class' => 'ClassA',
            'method' => 'methodA',
            'file' => 'ClassA.php',
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
            'file' => 'ClassB.php',
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
            'file' => 'ClassC.php',
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

    #[Test]
    public function testGetAverageScoreWithEmptyCollection(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();
        
        $result = $metricsCollection->getAverageScore();
        
        $this->assertEquals(0.0, $result);
    }

    #[Test]
    public function testGetAverageScoreWithSingleMetric(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();
        $metrics = $this->createCognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'testMethod',
            'file' => 'test.php',
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0
        ]);
        $metrics->setScore(2.5);
        $metricsCollection->add($metrics);
        
        $result = $metricsCollection->getAverageScore();
        
        $this->assertEquals(2.5, $result);
    }

    #[Test]
    public function testGetAverageScoreWithMultipleMetrics(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();
        
        $metrics1 = $this->createCognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'method1',
            'file' => 'test.php',
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0
        ]);
        $metrics1->setScore(1.0);
        
        $metrics2 = $this->createCognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'method2',
            'file' => 'test.php',
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0
        ]);
        $metrics2->setScore(2.0);
        
        $metrics3 = $this->createCognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'method3',
            'file' => 'test.php',
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0
        ]);
        $metrics3->setScore(3.0);
        
        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);
        $metricsCollection->add($metrics3);
        
        $result = $metricsCollection->getAverageScore();
        
        $this->assertEquals(2.0, $result);
    }

    #[Test]
    public function testCountMethodsExceedingThresholdWithEmptyCollection(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();
        
        $result = $metricsCollection->countMethodsExceedingThreshold(1.0);
        
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function testCountMethodsExceedingThresholdWithNoMethodsExceeding(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();
        
        $metrics1 = $this->createCognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'method1',
            'file' => 'test.php',
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0
        ]);
        $metrics1->setScore(0.5);
        
        $metrics2 = $this->createCognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'method2',
            'file' => 'test.php',
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0
        ]);
        $metrics2->setScore(0.8);
        
        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);
        
        $result = $metricsCollection->countMethodsExceedingThreshold(1.0);
        
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function testCountMethodsExceedingThresholdWithSomeMethodsExceeding(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();
        
        $metrics1 = $this->createCognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'method1',
            'file' => 'test.php',
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0
        ]);
        $metrics1->setScore(0.5);
        
        $metrics2 = $this->createCognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'method2',
            'file' => 'test.php',
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0
        ]);
        $metrics2->setScore(1.5);
        
        $metrics3 = $this->createCognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'method3',
            'file' => 'test.php',
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0
        ]);
        $metrics3->setScore(2.0);
        
        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);
        $metricsCollection->add($metrics3);
        
        $result = $metricsCollection->countMethodsExceedingThreshold(1.0);
        
        $this->assertEquals(2, $result);
    }

    #[Test]
    public function testCountMethodsExceedingThresholdWithAllMethodsExceeding(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();
        
        $metrics1 = $this->createCognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'method1',
            'file' => 'test.php',
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0
        ]);
        $metrics1->setScore(1.5);
        
        $metrics2 = $this->createCognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'method2',
            'file' => 'test.php',
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0
        ]);
        $metrics2->setScore(2.0);
        
        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);
        
        $result = $metricsCollection->countMethodsExceedingThreshold(1.0);
        
        $this->assertEquals(2, $result);
    }
}
