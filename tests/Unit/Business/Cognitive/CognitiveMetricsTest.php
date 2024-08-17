<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business\Cognitive;

use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetrics;
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
            'line_count' => 10,
            'arg_count' => 2,
            'return_count' => 1,
            'variable_count' => 5,
            'property_call_count' => 3,
            'if_count' => 4,
            'if_nesting_level' => 2,
            'else_count' => 1,
        ];
    }

    public function testConstructor(): void
    {
        $metrics = new \Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetrics($this->testMetricsData);

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
        $metrics = new \Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetrics($this->testMetricsData);

        $expectedArray = [
            'class' => 'TestClass',
            'method' => 'testMethod',
            'line_count' => 10,
            'arg_count' => 2,
            'return_count' => 1,
            'variable_count' => 5,
            'property_call_count' => 3,
            'if_count' => 4,
            'if_nesting_level' => 2,
            'else_count' => 1,
            'line_count_weight' => 0.0,
            'arg_count_weight' => 0.0,
            'return_count_weight' => 0.0,
            'variable_count_weight' => 0.0,
            'property_call_count_weight' => 0.0,
            'if_count_weight' => 0.0,
            'if_nesting_level_weight' => 0.0,
            'else_count_weight' => 0.0,
        ];

        $this->assertSame($expectedArray, $metrics->toArray());
    }

    public function testJsonSerialize(): void
    {
        $metrics = new \Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetrics($this->testMetricsData);

        $expectedArray = [
            'class' => 'TestClass',
            'method' => 'testMethod',
            'line_count' => 10,
            'arg_count' => 2,
            'return_count' => 1,
            'variable_count' => 5,
            'property_call_count' => 3,
            'if_count' => 4,
            'if_nesting_level' => 2,
            'else_count' => 1,
            'line_count_weight' => 0.0,
            'arg_count_weight' => 0.0,
            'return_count_weight' => 0.0,
            'variable_count_weight' => 0.0,
            'property_call_count_weight' => 0.0,
            'if_count_weight' => 0.0,
            'if_nesting_level_weight' => 0.0,
            'else_count_weight' => 0.0,
        ];

        $this->assertSame($expectedArray, $metrics->jsonSerialize());
    }
}
