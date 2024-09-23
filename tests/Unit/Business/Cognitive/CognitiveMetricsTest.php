<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business\Cognitive;

use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\Delta;
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

    public function testToArray(): void
    {
        $metrics = new CognitiveMetrics($this->testMetricsData);

        $expectedArray = [
            'class' => 'TestClass',
            'method' => 'testMethod',
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

    public function testJsonSerialize(): void
    {
        $metrics = new CognitiveMetrics($this->testMetricsData);

        $expectedArray = [
            'class' => 'TestClass',
            'method' => 'testMethod',
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

    public function testCalculateDeltas(): void
    {
        // Create first set of metrics
        $metrics1 = new CognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'testMethod',
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
        $metrics1->setLineCountWeight(1.5);
        $metrics1->setArgCountWeight(0.5);
        $metrics1->setReturnCountWeight(2.0);
        $metrics1->setVariableCountWeight(1.0);
        $metrics1->setPropertyCallCountWeight(1.5);
        $metrics1->setIfCountWeight(0.0);
        $metrics1->setIfNestingLevelWeight(1.0);
        $metrics1->setElseCountWeight(0.5);

        // Create second set of metrics with different weights
        $metrics2 = new CognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'testMethod',
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
        $metrics2->setLineCountWeight(1.0);
        $metrics2->setArgCountWeight(1.0);
        $metrics2->setReturnCountWeight(2.5);
        $metrics2->setVariableCountWeight(1.0);
        $metrics2->setPropertyCallCountWeight(2.0);
        $metrics2->setIfCountWeight(0.0);
        $metrics2->setIfNestingLevelWeight(2.0);
        $metrics2->setElseCountWeight(0.5);

        // Calculate deltas
        $metrics1->calculateDeltas($metrics2);

        // Check deltas for each weight
        $this->assertInstanceOf(Delta::class, $metrics1->getLineCountWeightDelta());
        $this->assertEquals(-0.5, $metrics1->getLineCountWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $metrics1->getArgCountWeightDelta());
        $this->assertEquals(0.5, $metrics1->getArgCountWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $metrics1->getReturnCountWeightDelta());
        $this->assertEquals(0.5, $metrics1->getReturnCountWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $metrics1->getVariableCountWeightDelta());
        $this->assertEquals(0.0, $metrics1->getVariableCountWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $metrics1->getPropertyCallCountWeightDelta());
        $this->assertEquals(0.5, $metrics1->getPropertyCallCountWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $metrics1->getIfCountWeightDelta());
        $this->assertEquals(0.0, $metrics1->getIfCountWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $metrics1->getIfNestingLevelWeightDelta());
        $this->assertEquals(1.0, $metrics1->getIfNestingLevelWeightDelta()->getValue());

        $this->assertInstanceOf(Delta::class, $metrics1->getElseCountWeightDelta());
        $this->assertEquals(0.0, $metrics1->getElseCountWeightDelta()->getValue());
    }
}
