<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\ClassMetricsStatistics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ClassMetricsStatisticsTest extends TestCase
{
    private ClassMetricsStatistics $statistics;

    protected function setUp(): void
    {
        $this->statistics = new ClassMetricsStatistics();
    }

    #[Test]
    public function testCalculateAverageScoreWithEmptyCollection(): void
    {
        $collection = new CognitiveMetricsCollection();
        
        $result = $this->statistics->calculateAverageScore($collection);
        
        $this->assertEquals(0.0, $result);
    }

    #[Test]
    public function testCalculateAverageScoreWithSingleMetric(): void
    {
        $collection = new CognitiveMetricsCollection();
        $metric = $this->createMetric('TestClass', 'testMethod', 2.5);
        $collection->add($metric);
        
        $result = $this->statistics->calculateAverageScore($collection);
        
        $this->assertEquals(2.5, $result);
    }

    #[Test]
    public function testCalculateAverageScoreWithMultipleMetrics(): void
    {
        $collection = new CognitiveMetricsCollection();
        $collection->add($this->createMetric('TestClass', 'method1', 1.0));
        $collection->add($this->createMetric('TestClass', 'method2', 2.0));
        $collection->add($this->createMetric('TestClass', 'method3', 3.0));
        
        $result = $this->statistics->calculateAverageScore($collection);
        
        $this->assertEquals(2.0, $result);
    }

    #[Test]
    public function testCountMethodsExceedingThresholdWithEmptyCollection(): void
    {
        $collection = new CognitiveMetricsCollection();
        
        $result = $this->statistics->countMethodsExceedingThreshold($collection, 1.0);
        
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function testCountMethodsExceedingThresholdWithNoMethodsExceeding(): void
    {
        $collection = new CognitiveMetricsCollection();
        $collection->add($this->createMetric('TestClass', 'method1', 0.5));
        $collection->add($this->createMetric('TestClass', 'method2', 0.8));
        
        $result = $this->statistics->countMethodsExceedingThreshold($collection, 1.0);
        
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function testCountMethodsExceedingThresholdWithSomeMethodsExceeding(): void
    {
        $collection = new CognitiveMetricsCollection();
        $collection->add($this->createMetric('TestClass', 'method1', 0.5));
        $collection->add($this->createMetric('TestClass', 'method2', 1.5));
        $collection->add($this->createMetric('TestClass', 'method3', 2.0));
        
        $result = $this->statistics->countMethodsExceedingThreshold($collection, 1.0);
        
        $this->assertEquals(2, $result);
    }

    #[Test]
    public function testCountMethodsExceedingThresholdWithAllMethodsExceeding(): void
    {
        $collection = new CognitiveMetricsCollection();
        $collection->add($this->createMetric('TestClass', 'method1', 1.5));
        $collection->add($this->createMetric('TestClass', 'method2', 2.0));
        
        $result = $this->statistics->countMethodsExceedingThreshold($collection, 1.0);
        
        $this->assertEquals(2, $result);
    }

    #[Test]
    public function testCalculatePercentageExceedingThresholdWithEmptyCollection(): void
    {
        $collection = new CognitiveMetricsCollection();
        
        $result = $this->statistics->calculatePercentageExceedingThreshold($collection, 1.0);
        
        $this->assertEquals(0.0, $result);
    }

    #[Test]
    public function testCalculatePercentageExceedingThresholdWithNoMethodsExceeding(): void
    {
        $collection = new CognitiveMetricsCollection();
        $collection->add($this->createMetric('TestClass', 'method1', 0.5));
        $collection->add($this->createMetric('TestClass', 'method2', 0.8));
        
        $result = $this->statistics->calculatePercentageExceedingThreshold($collection, 1.0);
        
        $this->assertEquals(0.0, $result);
    }

    #[Test]
    public function testCalculatePercentageExceedingThresholdWithHalfMethodsExceeding(): void
    {
        $collection = new CognitiveMetricsCollection();
        $collection->add($this->createMetric('TestClass', 'method1', 0.5));
        $collection->add($this->createMetric('TestClass', 'method2', 1.5));
        
        $result = $this->statistics->calculatePercentageExceedingThreshold($collection, 1.0);
        
        $this->assertEquals(50.0, $result);
    }

    #[Test]
    public function testCalculatePercentageExceedingThresholdWithAllMethodsExceeding(): void
    {
        $collection = new CognitiveMetricsCollection();
        $collection->add($this->createMetric('TestClass', 'method1', 1.5));
        $collection->add($this->createMetric('TestClass', 'method2', 2.0));
        
        $result = $this->statistics->calculatePercentageExceedingThreshold($collection, 1.0);
        
        $this->assertEquals(100.0, $result);
    }

    #[Test]
    public function testHasMethodsExceedingThresholdWithEmptyCollection(): void
    {
        $collection = new CognitiveMetricsCollection();
        
        $result = $this->statistics->hasMethodsExceedingThreshold($collection, 1.0);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function testHasMethodsExceedingThresholdWithNoMethodsExceeding(): void
    {
        $collection = new CognitiveMetricsCollection();
        $collection->add($this->createMetric('TestClass', 'method1', 0.5));
        $collection->add($this->createMetric('TestClass', 'method2', 0.8));
        
        $result = $this->statistics->hasMethodsExceedingThreshold($collection, 1.0);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function testHasMethodsExceedingThresholdWithSomeMethodsExceeding(): void
    {
        $collection = new CognitiveMetricsCollection();
        $collection->add($this->createMetric('TestClass', 'method1', 0.5));
        $collection->add($this->createMetric('TestClass', 'method2', 1.5));
        
        $result = $this->statistics->hasMethodsExceedingThreshold($collection, 1.0);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function testCalculateOverallStatisticsWithEmptyCollections(): void
    {
        $groupedCollections = [];
        
        $result = $this->statistics->calculateOverallStatistics($groupedCollections, 1.0);
        
        $this->assertEquals(0, $result['totalClasses']);
        $this->assertEquals(0, $result['classesExceedingThreshold']);
        $this->assertEquals(0.0, $result['percentageExceedingThreshold']);
    }

    #[Test]
    public function testCalculateOverallStatisticsWithNoClassesExceeding(): void
    {
        $class1 = new CognitiveMetricsCollection();
        $class1->add($this->createMetric('Class1', 'method1', 0.5));
        
        $class2 = new CognitiveMetricsCollection();
        $class2->add($this->createMetric('Class2', 'method1', 0.8));
        
        $groupedCollections = ['Class1' => $class1, 'Class2' => $class2];
        
        $result = $this->statistics->calculateOverallStatistics($groupedCollections, 1.0);
        
        $this->assertEquals(2, $result['totalClasses']);
        $this->assertEquals(0, $result['classesExceedingThreshold']);
        $this->assertEquals(0.0, $result['percentageExceedingThreshold']);
    }

    #[Test]
    public function testCalculateOverallStatisticsWithSomeClassesExceeding(): void
    {
        $class1 = new CognitiveMetricsCollection();
        $class1->add($this->createMetric('Class1', 'method1', 0.5));
        
        $class2 = new CognitiveMetricsCollection();
        $class2->add($this->createMetric('Class2', 'method1', 1.5));
        
        $class3 = new CognitiveMetricsCollection();
        $class3->add($this->createMetric('Class3', 'method1', 0.8));
        
        $groupedCollections = ['Class1' => $class1, 'Class2' => $class2, 'Class3' => $class3];
        
        $result = $this->statistics->calculateOverallStatistics($groupedCollections, 1.0);
        
        $this->assertEquals(3, $result['totalClasses']);
        $this->assertEquals(1, $result['classesExceedingThreshold']);
        $this->assertEquals(33.3, $result['percentageExceedingThreshold']);
    }

    #[Test]
    public function testCalculateOverallStatisticsWithAllClassesExceeding(): void
    {
        $class1 = new CognitiveMetricsCollection();
        $class1->add($this->createMetric('Class1', 'method1', 1.5));
        
        $class2 = new CognitiveMetricsCollection();
        $class2->add($this->createMetric('Class2', 'method1', 2.0));
        
        $groupedCollections = ['Class1' => $class1, 'Class2' => $class2];
        
        $result = $this->statistics->calculateOverallStatistics($groupedCollections, 1.0);
        
        $this->assertEquals(2, $result['totalClasses']);
        $this->assertEquals(2, $result['classesExceedingThreshold']);
        $this->assertEquals(100.0, $result['percentageExceedingThreshold']);
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
