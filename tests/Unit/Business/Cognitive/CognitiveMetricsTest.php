<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Delta;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class CognitiveMetricsTest extends TestCase
{
    private array $testMetricsData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testMetricsData = [
            'class' => 'TestClass',
            'method' => 'testMethod',
            'file' => 'TestClass.php',
            'lineCount' => 10,
            'argCount' => 2,
            'returnCount' => 1,
            'variableCount' => 5,
            'propertyCallCount' => 3,
            'ifCount' => 4,
            'ifNestingLevel' => 2,
            'elseCount' => 1,
        ];
    }

    #[Test]
    public function testConstructor(): void
    {
        $metrics = new CognitiveMetrics($this->testMetricsData);

        $this->assertSame('TestClass', $metrics->getClass());
        $this->assertSame('testMethod', $metrics->getMethod());
        $this->assertSame(10, $metrics->getLineCount());
        $this->assertSame(2, $metrics->getArgCount());
        $this->assertSame(1, $metrics->getReturnCount());
        $this->assertSame(5, $metrics->getVariableCount());
        $this->assertSame(3, $metrics->getPropertyCallCount());
        $this->assertSame(4, $metrics->getIfCount());
        $this->assertSame(2, $metrics->getIfNestingLevel());
        $this->assertSame(1, $metrics->getElseCount());
        $this->assertSame(0.0, $metrics->getLineCountWeight());
        $this->assertSame(0.0, $metrics->getArgCountWeight());
        $this->assertSame(0.0, $metrics->getReturnCountWeight());
        $this->assertSame(0.0, $metrics->getVariableCountWeight());
        $this->assertSame(0.0, $metrics->getPropertyCallCountWeight());
        $this->assertSame(0.0, $metrics->getIfCountWeight());
        $this->assertSame(0.0, $metrics->getIfNestingLevelWeight());
        $this->assertSame(0.0, $metrics->getElseCountWeight());
    }

    #[Test]
    public function testFromArray(): void
    {
        $metrics = CognitiveMetrics::fromArray($this->testMetricsData);

        $this->assertSame('TestClass', $metrics->getClass());
        $this->assertSame('testMethod', $metrics->getMethod());
        $this->assertSame(10, $metrics->getLineCount());
        $this->assertSame(2, $metrics->getArgCount());
        $this->assertSame(1, $metrics->getReturnCount());
        $this->assertSame(5, $metrics->getVariableCount());
        $this->assertSame(3, $metrics->getPropertyCallCount());
        $this->assertSame(4, $metrics->getIfCount());
        $this->assertSame(2, $metrics->getIfNestingLevel());
        $this->assertSame(1, $metrics->getElseCount());
        $this->assertSame(0.0, $metrics->getLineCountWeight());
        $this->assertSame(0.0, $metrics->getArgCountWeight());
        $this->assertSame(0.0, $metrics->getReturnCountWeight());
        $this->assertSame(0.0, $metrics->getVariableCountWeight());
        $this->assertSame(0.0, $metrics->getPropertyCallCountWeight());
        $this->assertSame(0.0, $metrics->getIfCountWeight());
        $this->assertSame(0.0, $metrics->getIfNestingLevelWeight());
        $this->assertSame(0.0, $metrics->getElseCountWeight());
    }

    #[Test]
    public function testToArray(): void
    {
        $metrics = new CognitiveMetrics($this->testMetricsData);

        $expectedArray = [
            'class' => 'TestClass',
            'method' => 'testMethod',
            'file' => 'TestClass.php',
            'lineCount' => 10,
            'argCount' => 2,
            'returnCount' => 1,
            'variableCount' => 5,
            'propertyCallCount' => 3,
            'ifCount' => 4,
            'ifNestingLevel' => 2,
            'elseCount' => 1,
            'lineCountWeight' => 0.0,
            'argCountWeight' => 0.0,
            'returnCountWeight' => 0.0,
            'variableCountWeight' => 0.0,
            'propertyCallCountWeight' => 0.0,
            'ifCountWeight' => 0.0,
            'ifNestingLevelWeight' => 0.0,
            'elseCountWeight' => 0.0,
            'lineCountWeightDelta' => null,
            'argCountWeightDelta' => null,
            'returnCountWeightDelta' => null,
            'variableCountWeightDelta' => null,
            'propertyCallCountWeightDelta' => null,
            'ifCountWeightDelta' => null,
            'ifNestingLevelWeightDelta' => null,
            'elseCountWeightDelta' => null,
        ];

        $this->assertSame($expectedArray, $metrics->toArray());
    }

    #[Test]
    public function testJsonSerialize(): void
    {
        $metrics = new CognitiveMetrics($this->testMetricsData);

        $expectedArray = [
            'class' => 'TestClass',
            'method' => 'testMethod',
            'file' => 'TestClass.php',
            'lineCount' => 10,
            'argCount' => 2,
            'returnCount' => 1,
            'variableCount' => 5,
            'propertyCallCount' => 3,
            'ifCount' => 4,
            'ifNestingLevel' => 2,
            'elseCount' => 1,
            'lineCountWeight' => 0.0,
            'argCountWeight' => 0.0,
            'returnCountWeight' => 0.0,
            'variableCountWeight' => 0.0,
            'propertyCallCountWeight' => 0.0,
            'ifCountWeight' => 0.0,
            'ifNestingLevelWeight' => 0.0,
            'elseCountWeight' => 0.0,
            'lineCountWeightDelta' => null,
            'argCountWeightDelta' => null,
            'returnCountWeightDelta' => null,
            'variableCountWeightDelta' => null,
            'propertyCallCountWeightDelta' => null,
            'ifCountWeightDelta' => null,
            'ifNestingLevelWeightDelta' => null,
            'elseCountWeightDelta' => null,
        ];

        $this->assertSame($expectedArray, $metrics->jsonSerialize());
    }

    #[Test]
    public function testCalculateDeltas(): void
    {
        // Create first set of metrics
        $presentMetrics = new CognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'testMethod',
            'file' => 'TestClass.php',
            'lineCount' => 10,
            'argCount' => 2,
            'returnCount' => 1,
            'variableCount' => 5,
            'propertyCallCount' => 3,
            'ifCount' => 4,
            'ifNestingLevel' => 2,
            'elseCount' => 1,
        ]);

        // Set weights for metrics1
        $presentMetrics->setLineCountWeight(1.5);
        $presentMetrics->setArgCountWeight(0.5);
        $presentMetrics->setReturnCountWeight(2.0);
        $presentMetrics->setVariableCountWeight(1.0);
        $presentMetrics->setPropertyCallCountWeight(1.5);
        $presentMetrics->setIfCountWeight(0.0);
        $presentMetrics->setIfNestingLevelWeight(1.0);
        $presentMetrics->setElseCountWeight(0.5);

        // Create second set of metrics with different weights
        $beforeMetrics = new CognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'testMethod',
            'file' => 'TestClass.php',
            'lineCount' => 10,
            'argCount' => 2,
            'returnCount' => 1,
            'variableCount' => 5,
            'propertyCallCount' => 3,
            'ifCount' => 4,
            'ifNestingLevel' => 2,
            'elseCount' => 1,
        ]);

        // Set weights for metrics2
        $beforeMetrics->setLineCountWeight(1.0);
        $beforeMetrics->setArgCountWeight(1.0);
        $beforeMetrics->setReturnCountWeight(2.5);
        $beforeMetrics->setVariableCountWeight(1.0);
        $beforeMetrics->setPropertyCallCountWeight(2.0);
        $beforeMetrics->setIfCountWeight(0.0);
        $beforeMetrics->setIfNestingLevelWeight(2.0);
        $beforeMetrics->setElseCountWeight(0.5);

        // Calculate deltas
        $presentMetrics->calculateDeltas($beforeMetrics);

        // Check deltas for each weight
        $this->assertInstanceOf(Delta::class, $presentMetrics->getLineCountWeightDelta());
        $this->assertEquals(0.5, $presentMetrics->getLineCountWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $presentMetrics->getArgCountWeightDelta());
        $this->assertEquals(-0.5, $presentMetrics->getArgCountWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $presentMetrics->getReturnCountWeightDelta());
        $this->assertEquals(-0.5, $presentMetrics->getReturnCountWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $presentMetrics->getVariableCountWeightDelta());
        $this->assertEquals(0.0, $presentMetrics->getVariableCountWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $presentMetrics->getPropertyCallCountWeightDelta());
        $this->assertEquals(-0.5, $presentMetrics->getPropertyCallCountWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $presentMetrics->getIfCountWeightDelta());
        $this->assertEquals(0.0, $presentMetrics->getIfCountWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $presentMetrics->getIfNestingLevelWeightDelta());
        $this->assertEquals(-1.0, $presentMetrics->getIfNestingLevelWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $presentMetrics->getElseCountWeightDelta());
        $this->assertEquals(0.0, $presentMetrics->getElseCountWeightDelta()->getValue());
    }
}
