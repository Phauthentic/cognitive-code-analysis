<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsSorter;
use Phauthentic\CognitiveCodeAnalysis\Business\Cyclomatic\CyclomaticMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Halstead\HalsteadMetrics;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CognitiveMetricsSorterTest extends TestCase
{
    private CognitiveMetricsSorter $sorter;

    protected function setUp(): void
    {
        $this->sorter = new CognitiveMetricsSorter();
    }

    #[Test]
    public function testSortByScoreAscending(): void
    {
        $collection = $this->createTestCollection();

        // Debug: Check collection size
        $this->assertEquals(3, $collection->count());

        $sorted = $this->sorter->sort($collection, 'score', 'asc');

        $metrics = iterator_to_array($sorted, true);
        $metricsArray = array_values($metrics);
        $this->assertEquals(1.0, $metricsArray[0]->getScore());
        $this->assertEquals(5.0, $metricsArray[1]->getScore());
        $this->assertEquals(10.0, $metricsArray[2]->getScore());
    }

    #[Test]
    public function testSortByScoreDescending(): void
    {
        $collection = $this->createTestCollection();
        $sorted = $this->sorter->sort($collection, 'score', 'desc');

        $metrics = iterator_to_array($sorted, true);
        $metricsArray = array_values($metrics);
        $this->assertEquals(10.0, $metricsArray[0]->getScore());
        $this->assertEquals(5.0, $metricsArray[1]->getScore());
        $this->assertEquals(1.0, $metricsArray[2]->getScore());
    }

    #[Test]
    public function testSortByClassAscending(): void
    {
        $collection = $this->createTestCollection();
        $sorted = $this->sorter->sort($collection, 'class', 'asc');

        $metrics = iterator_to_array($sorted, true);
        $metricsArray = array_values($metrics);
        $this->assertEquals('ClassA', $metricsArray[0]->getClass());
        $this->assertEquals('ClassB', $metricsArray[1]->getClass());
        $this->assertEquals('ClassC', $metricsArray[2]->getClass());
    }

    #[Test]
    public function testSortByMethodAscending(): void
    {
        $collection = $this->createTestCollection();
        $sorted = $this->sorter->sort($collection, 'method', 'asc');

        $metrics = iterator_to_array($sorted, true);
        $metricsArray = array_values($metrics);
        $this->assertEquals('method1', $metricsArray[0]->getMethod());
        $this->assertEquals('method2', $metricsArray[1]->getMethod());
        $this->assertEquals('method3', $metricsArray[2]->getMethod());
    }

    #[Test]
    public function testSortByHalsteadVolume(): void
    {
        $collection = $this->createTestCollection();
        $sorted = $this->sorter->sort($collection, 'halstead', 'asc');

        $metrics = iterator_to_array($sorted, true);
        $metricsArray = array_values($metrics);
        $this->assertEquals(100.0, $metricsArray[0]->getHalstead()?->getVolume());
        $this->assertEquals(200.0, $metricsArray[1]->getHalstead()?->getVolume());
        $this->assertEquals(300.0, $metricsArray[2]->getHalstead()?->getVolume());
    }

    #[Test]
    public function testSortByCyclomaticComplexity(): void
    {
        $collection = $this->createTestCollection();
        $sorted = $this->sorter->sort($collection, 'cyclomatic', 'asc');

        $metrics = iterator_to_array($sorted, true);
        $metricsArray = array_values($metrics);
        $this->assertEquals(1, $metricsArray[0]->getCyclomatic()?->complexity);
        $this->assertEquals(3, $metricsArray[1]->getCyclomatic()?->complexity);
        $this->assertEquals(5, $metricsArray[2]->getCyclomatic()?->complexity);
    }

    #[Test]
    public function testInvalidSortField(): void
    {
        $collection = $this->createTestCollection();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort field "invalid_field"');

        $this->sorter->sort($collection, 'invalid_field');
    }

    #[Test]
    public function testInvalidSortOrder(): void
    {
        $collection = $this->createTestCollection();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sort order must be "asc" or "desc"');

        $this->sorter->sort($collection, 'score', 'invalid');
    }

    #[Test]
    public function testGetSortableFields(): void
    {
        $fields = $this->sorter->getSortableFields();

        $this->assertContains('score', $fields);
        $this->assertContains('halstead', $fields);
        $this->assertContains('cyclomatic', $fields);
        $this->assertContains('class', $fields);
        $this->assertContains('method', $fields);
    }

    private function createTestCollection(): CognitiveMetricsCollection
    {
        $collection = new CognitiveMetricsCollection();

        // Create metrics with different values for testing
        $metrics1 = new CognitiveMetrics([
            'class' => 'ClassA',
            'method' => 'method1',
            'file' => '/test/file1.php',
            'lineCount' => 10,
            'argCount' => 1,
            'returnCount' => 1,
            'variableCount' => 1,
            'propertyCallCount' => 1,
            'ifCount' => 1,
            'ifNestingLevel' => 1,
            'elseCount' => 1,
        ]);
        $metrics1->setScore(10.0);

        $metrics2 = new CognitiveMetrics([
            'class' => 'ClassB',
            'method' => 'method2',
            'file' => '/test/file2.php',
            'lineCount' => 20,
            'argCount' => 2,
            'returnCount' => 2,
            'variableCount' => 2,
            'propertyCallCount' => 2,
            'ifCount' => 2,
            'ifNestingLevel' => 2,
            'elseCount' => 2,
        ]);
        $metrics2->setScore(5.0);

        $metrics3 = new CognitiveMetrics([
            'class' => 'ClassC',
            'method' => 'method3',
            'file' => '/test/file3.php',
            'lineCount' => 30,
            'argCount' => 3,
            'returnCount' => 3,
            'variableCount' => 3,
            'propertyCallCount' => 3,
            'ifCount' => 3,
            'ifNestingLevel' => 3,
            'elseCount' => 3,
        ]);
        $metrics3->setScore(1.0);

        // Add Halstead metrics
        $halstead1 = new HalsteadMetrics(['volume' => 100.0, 'n1' => 1, 'n2' => 1, 'N1' => 1, 'N2' => 1, 'programLength' => 1, 'programVocabulary' => 1, 'difficulty' => 1.0, 'effort' => 1.0, 'fqName' => 'test']);
        $halstead2 = new HalsteadMetrics(['volume' => 200.0, 'n1' => 1, 'n2' => 1, 'N1' => 1, 'N2' => 1, 'programLength' => 1, 'programVocabulary' => 1, 'difficulty' => 1.0, 'effort' => 1.0, 'fqName' => 'test']);
        $halstead3 = new HalsteadMetrics(['volume' => 300.0, 'n1' => 1, 'n2' => 1, 'N1' => 1, 'N2' => 1, 'programLength' => 1, 'programVocabulary' => 1, 'difficulty' => 1.0, 'effort' => 1.0, 'fqName' => 'test']);

        // Add Cyclomatic metrics
        $cyclomatic1 = new CyclomaticMetrics(['complexity' => 1]);
        $cyclomatic2 = new CyclomaticMetrics(['complexity' => 3]);
        $cyclomatic3 = new CyclomaticMetrics(['complexity' => 5]);

        // Use reflection to set private properties for testing
        $reflection = new \ReflectionClass($metrics1);
        $halsteadProperty = $reflection->getProperty('halstead');
        $halsteadProperty->setAccessible(true);
        $halsteadProperty->setValue($metrics1, $halstead1);

        $cyclomaticProperty = $reflection->getProperty('cyclomatic');
        $cyclomaticProperty->setAccessible(true);
        $cyclomaticProperty->setValue($metrics1, $cyclomatic1);

        $reflection = new \ReflectionClass($metrics2);
        $halsteadProperty = $reflection->getProperty('halstead');
        $halsteadProperty->setAccessible(true);
        $halsteadProperty->setValue($metrics2, $halstead2);

        $cyclomaticProperty = $reflection->getProperty('cyclomatic');
        $cyclomaticProperty->setAccessible(true);
        $cyclomaticProperty->setValue($metrics2, $cyclomatic2);

        $reflection = new \ReflectionClass($metrics3);
        $halsteadProperty = $reflection->getProperty('halstead');
        $halsteadProperty->setAccessible(true);
        $halsteadProperty->setValue($metrics3, $halstead3);

        $cyclomaticProperty = $reflection->getProperty('cyclomatic');
        $cyclomaticProperty->setAccessible(true);
        $cyclomaticProperty->setValue($metrics3, $cyclomatic3);

        $collection->add($metrics1);
        $collection->add($metrics2);
        $collection->add($metrics3);

        return $collection;
    }
}
