<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\CyclomaticComplexity;

use Phauthentic\CognitiveCodeAnalysis\Business\Cyclomatic\CyclomaticComplexityCalculator;
use PHPUnit\Framework\TestCase;

class CyclomaticComplexityCalculatorTest extends TestCase
{
    private CyclomaticComplexityCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new CyclomaticComplexityCalculator();
    }

    public function testCalculateComplexityWithEmptyCounts(): void
    {
        $decisionPointCounts = [];
        $result = $this->calculator->calculateComplexity($decisionPointCounts);

        $this->assertEquals(1, $result, 'Base complexity should be 1');
    }

    public function testCalculateComplexityWithVariousCounts(): void
    {
        $decisionPointCounts = [
            'if' => 2,
            'while' => 1,
            'for' => 1,
            'switch' => 1,
            'case' => 3,
            'logical_and' => 2,
            'logical_or' => 1,
        ];

        $result = $this->calculator->calculateComplexity($decisionPointCounts);

        // Base complexity (1) + sum of all counts (2+1+1+1+3+2+1 = 11) = 12
        $this->assertEquals(12, $result, 'Complexity should be base + sum of counts');
    }

    public function testCalculateComplexityWithZeroCounts(): void
    {
        $decisionPointCounts = [
            'if' => 0,
            'while' => 0,
            'for' => 0,
            'switch' => 0,
            'case' => 0,
            'logical_and' => 0,
            'logical_or' => 0,
        ];

        $result = $this->calculator->calculateComplexity($decisionPointCounts);

        $this->assertEquals(1, $result, 'Complexity should be base complexity only');
    }

    public function testCreateBreakdown(): void
    {
        $decisionPointCounts = [
            'if' => 2,
            'while' => 1,
            'logical_and' => 1,
        ];
        $totalComplexity = 5;

        $result = $this->calculator->createBreakdown($decisionPointCounts, $totalComplexity);

        $expected = [
            'total' => 5,
            'base' => 1,
            'if' => 2,
            'while' => 1,
            'logical_and' => 1,
        ];

        $this->assertEquals($expected, $result, 'Breakdown should include total, base, and all counts');
    }

    public function testCreateBreakdownWithEmptyCounts(): void
    {
        $decisionPointCounts = [];
        $totalComplexity = 1;

        $result = $this->calculator->createBreakdown($decisionPointCounts, $totalComplexity);

        $expected = [
            'total' => 1,
            'base' => 1,
        ];

        $this->assertEquals($expected, $result, 'Breakdown should include only total and base for empty counts');
    }

    public function testGetRiskLevelLow(): void
    {
        $this->assertEquals('low', $this->calculator->getRiskLevel(1), 'Complexity 1 should be low risk');
        $this->assertEquals('low', $this->calculator->getRiskLevel(3), 'Complexity 3 should be low risk');
        $this->assertEquals('low', $this->calculator->getRiskLevel(5), 'Complexity 5 should be low risk');
    }

    public function testGetRiskLevelMedium(): void
    {
        $this->assertEquals('medium', $this->calculator->getRiskLevel(6), 'Complexity 6 should be medium risk');
        $this->assertEquals('medium', $this->calculator->getRiskLevel(8), 'Complexity 8 should be medium risk');
        $this->assertEquals('medium', $this->calculator->getRiskLevel(10), 'Complexity 10 should be medium risk');
    }

    public function testGetRiskLevelHigh(): void
    {
        $this->assertEquals('high', $this->calculator->getRiskLevel(11), 'Complexity 11 should be high risk');
        $this->assertEquals('high', $this->calculator->getRiskLevel(13), 'Complexity 13 should be high risk');
        $this->assertEquals('high', $this->calculator->getRiskLevel(15), 'Complexity 15 should be high risk');
    }

    public function testGetRiskLevelVeryHigh(): void
    {
        $this->assertEquals('very_high', $this->calculator->getRiskLevel(16), 'Complexity 16 should be very high risk');
        $this->assertEquals('very_high', $this->calculator->getRiskLevel(25), 'Complexity 25 should be very high risk');
        $this->assertEquals('very_high', $this->calculator->getRiskLevel(100), 'Complexity 100 should be very high risk');
    }

    public function testCreateSummaryWithEmptyData(): void
    {
        $classComplexities = [];
        $methodComplexities = [];
        $methodBreakdowns = [];

        $result = $this->calculator->createSummary($classComplexities, $methodComplexities, $methodBreakdowns);

        $expected = [
            'classes' => [],
            'methods' => [],
            'high_risk_methods' => [],
            'very_high_risk_methods' => [],
        ];

        $this->assertEquals($expected, $result, 'Summary should have empty arrays for empty input');
    }

    public function testCreateSummaryWithClassData(): void
    {
        $classComplexities = [
            '\\Test\\Class1' => 5,
            '\\Test\\Class2' => 12,
        ];
        $methodComplexities = [];
        $methodBreakdowns = [];

        $result = $this->calculator->createSummary($classComplexities, $methodComplexities, $methodBreakdowns);

        $this->assertArrayHasKey('classes', $result);
        $this->assertArrayHasKey('\\Test\\Class1', $result['classes']);
        $this->assertArrayHasKey('\\Test\\Class2', $result['classes']);

        $this->assertEquals(5, $result['classes']['\\Test\\Class1']['complexity']);
        $this->assertEquals('low', $result['classes']['\\Test\\Class1']['risk_level']);

        $this->assertEquals(12, $result['classes']['\\Test\\Class2']['complexity']);
        $this->assertEquals('high', $result['classes']['\\Test\\Class2']['risk_level']);
    }

    public function testCreateSummaryWithMethodData(): void
    {
        $classComplexities = [];
        $methodComplexities = [
            '\\Test\\Class::simpleMethod' => 3,
            '\\Test\\Class::complexMethod' => 12,
            '\\Test\\Class::veryComplexMethod' => 20,
        ];
        $methodBreakdowns = [
            '\\Test\\Class::simpleMethod' => ['total' => 3, 'base' => 1, 'if' => 2],
            '\\Test\\Class::complexMethod' => ['total' => 12, 'base' => 1, 'if' => 5, 'while' => 3, 'logical_and' => 3],
            '\\Test\\Class::veryComplexMethod' => ['total' => 20, 'base' => 1, 'if' => 8, 'for' => 4, 'switch' => 2, 'case' => 5],
        ];

        $result = $this->calculator->createSummary($classComplexities, $methodComplexities, $methodBreakdowns);

        // Check methods
        $this->assertArrayHasKey('methods', $result);
        $this->assertArrayHasKey('\\Test\\Class::simpleMethod', $result['methods']);
        $this->assertArrayHasKey('\\Test\\Class::complexMethod', $result['methods']);
        $this->assertArrayHasKey('\\Test\\Class::veryComplexMethod', $result['methods']);

        // Check simple method (low risk)
        $this->assertEquals(3, $result['methods']['\\Test\\Class::simpleMethod']['complexity']);
        $this->assertEquals('low', $result['methods']['\\Test\\Class::simpleMethod']['risk_level']);
        $this->assertEquals(['total' => 3, 'base' => 1, 'if' => 2], $result['methods']['\\Test\\Class::simpleMethod']['breakdown']);

        // Check complex method (high risk)
        $this->assertEquals(12, $result['methods']['\\Test\\Class::complexMethod']['complexity']);
        $this->assertEquals('high', $result['methods']['\\Test\\Class::complexMethod']['risk_level']);

        // Check very complex method (very high risk)
        $this->assertEquals(20, $result['methods']['\\Test\\Class::veryComplexMethod']['complexity']);
        $this->assertEquals('very_high', $result['methods']['\\Test\\Class::veryComplexMethod']['risk_level']);

        // Check high risk methods (>= 10)
        $this->assertArrayHasKey('high_risk_methods', $result);
        $this->assertArrayHasKey('\\Test\\Class::complexMethod', $result['high_risk_methods']);
        $this->assertArrayHasKey('\\Test\\Class::veryComplexMethod', $result['high_risk_methods']);
        $this->assertEquals(12, $result['high_risk_methods']['\\Test\\Class::complexMethod']);
        $this->assertEquals(20, $result['high_risk_methods']['\\Test\\Class::veryComplexMethod']);

        // Check very high risk methods (>= 15)
        $this->assertArrayHasKey('very_high_risk_methods', $result);
        $this->assertArrayHasKey('\\Test\\Class::veryComplexMethod', $result['very_high_risk_methods']);
        $this->assertEquals(20, $result['very_high_risk_methods']['\\Test\\Class::veryComplexMethod']);

        // Simple method should not be in high risk lists
        $this->assertArrayNotHasKey('\\Test\\Class::simpleMethod', $result['high_risk_methods']);
        $this->assertArrayNotHasKey('\\Test\\Class::simpleMethod', $result['very_high_risk_methods']);

        // Complex method should not be in very high risk list
        $this->assertArrayNotHasKey('\\Test\\Class::complexMethod', $result['very_high_risk_methods']);
    }

    public function testCreateSummaryWithMissingBreakdown(): void
    {
        $classComplexities = [];
        $methodComplexities = [
            '\\Test\\Class::methodWithoutBreakdown' => 5,
        ];
        $methodBreakdowns = []; // Missing breakdown

        $result = $this->calculator->createSummary($classComplexities, $methodComplexities, $methodBreakdowns);

        $this->assertArrayHasKey('methods', $result);
        $this->assertArrayHasKey('\\Test\\Class::methodWithoutBreakdown', $result['methods']);
        $this->assertEquals([], $result['methods']['\\Test\\Class::methodWithoutBreakdown']['breakdown']);
    }
}
