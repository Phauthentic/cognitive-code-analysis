<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Halstead;

use Phauthentic\CognitiveCodeAnalysis\Business\Halstead\HalsteadMetricsCalculator;
use PHPUnit\Framework\TestCase;

class HalsteadMetricsCalculatorTest extends TestCase
{
    private HalsteadMetricsCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new HalsteadMetricsCalculator();
    }

    public function testCalculateProgramLength(): void
    {
        $this->assertEquals(5, $this->calculator->calculateProgramLength(2, 3));
        $this->assertEquals(0, $this->calculator->calculateProgramLength(0, 0));
        $this->assertEquals(10, $this->calculator->calculateProgramLength(7, 3));
    }

    public function testCalculateProgramVocabulary(): void
    {
        $this->assertEquals(5, $this->calculator->calculateProgramVocabulary(2, 3));
        $this->assertEquals(0, $this->calculator->calculateProgramVocabulary(0, 0));
        $this->assertEquals(10, $this->calculator->calculateProgramVocabulary(7, 3));
    }

    public function testCalculateVolume(): void
    {
        // Test with normal values: 10 * log(4, 2) = 10 * 2 = 20
        $this->assertEquals(20.0, $this->calculator->calculateVolume(10, 4), 'Volume calculation with normal values');

        // Test with zero vocabulary (edge case) - should return 0
        $this->assertEquals(0.0, $this->calculator->calculateVolume(5, 0), 'Volume calculation with zero vocabulary');

        // Test with single vocabulary: 5 * log(1, 2) = 5 * 0 = 0
        $this->assertEquals(0.0, $this->calculator->calculateVolume(5, 1), 'Volume calculation with single vocabulary');
    }

    public function testCalculateDifficulty(): void
    {
        // Test normal case
        $this->assertEquals(2.0, $this->calculator->calculateDifficulty(2, 4, 2), 'Difficulty calculation with normal values');

        // Test edge case: zero distinct operands
        $this->assertEquals(0.0, $this->calculator->calculateDifficulty(2, 4, 0), 'Difficulty calculation with zero distinct operands');

        // Test edge case: zero operators
        $this->assertEquals(0.0, $this->calculator->calculateDifficulty(0, 4, 2), 'Difficulty calculation with zero operators');

        // Test edge case: zero total operands
        $this->assertEquals(0.0, $this->calculator->calculateDifficulty(2, 0, 2), 'Difficulty calculation with zero total operands');
    }

    public function testCalculateMetricsWithEmptyArrays(): void
    {
        $result = $this->calculator->calculateMetrics([], [], 'EmptyTest');

        $this->assertEquals(0, $result['n1'], 'Distinct operators should be 0');
        $this->assertEquals(0, $result['n2'], 'Distinct operands should be 0');
        $this->assertEquals(0, $result['N1'], 'Total operators should be 0');
        $this->assertEquals(0, $result['N2'], 'Total operands should be 0');
        $this->assertEquals(0, $result['programLength'], 'Program length should be 0');
        $this->assertEquals(0, $result['programVocabulary'], 'Program vocabulary should be 0');
        $this->assertEquals(0.0, $result['volume'], 'Volume should be 0');
        $this->assertEquals(0.0, $result['difficulty'], 'Difficulty should be 0');
        $this->assertEquals(0.0, $result['effort'], 'Effort should be 0');
        $this->assertEquals('EmptyTest', $result['fqName'], 'Identifier should be preserved');
    }

    public function testCalculateMetricsWithSimpleData(): void
    {
        $operators = ['+', '=', '+'];
        $operands = ['$a', '$b', '$c', '$a'];
        $identifier = 'TestClass::testMethod';

        $result = $this->calculator->calculateMetrics($operators, $operands, $identifier);

        // Check basic counts
        $this->assertEquals(2, $result['n1'], 'Should have 2 distinct operators');
        $this->assertEquals(3, $result['n2'], 'Should have 3 distinct operands');
        $this->assertEquals(3, $result['N1'], 'Should have 3 total operators');
        $this->assertEquals(4, $result['N2'], 'Should have 4 total operands');

        // Check calculated metrics
        $this->assertEquals(7, $result['programLength'], 'Program length should be 7');
        $this->assertEquals(5, $result['programVocabulary'], 'Program vocabulary should be 5');

        // Check advanced metrics
        $expectedVolume = 7 * log(5, 2);
        $this->assertEquals($expectedVolume, $result['volume'], 'Volume calculation should be correct');

        $expectedDifficulty = (2 / 2) * (4 / 3);
        $this->assertEquals($expectedDifficulty, $result['difficulty'], 'Difficulty calculation should be correct');

        $expectedEffort = $expectedDifficulty * $expectedVolume;
        $this->assertEquals($expectedEffort, $result['effort'], 'Effort calculation should be correct');

        $this->assertEquals($identifier, $result['fqName'], 'Identifier should be preserved');
    }

    public function testCalculateMetricsWithDuplicateOperatorsAndOperands(): void
    {
        $operators = ['+', '+', '+', '+'];
        $operands = ['$a', '$a', '$a', '$a'];
        $identifier = 'TestClass::duplicateMethod';

        $result = $this->calculator->calculateMetrics($operators, $operands, $identifier);

        // Check basic counts
        $this->assertEquals(1, $result['n1'], 'Should have 1 distinct operator');
        $this->assertEquals(1, $result['n2'], 'Should have 1 distinct operand');
        $this->assertEquals(4, $result['N1'], 'Should have 4 total operators');
        $this->assertEquals(4, $result['N2'], 'Should have 4 total operands');

        // Check calculated metrics
        $this->assertEquals(8, $result['programLength'], 'Program length should be 8');
        $this->assertEquals(2, $result['programVocabulary'], 'Program vocabulary should be 2');

        // Check advanced metrics
        $expectedVolume = 8 * log(2, 2);
        $this->assertEquals($expectedVolume, $result['volume'], 'Volume calculation should be correct');

        $expectedDifficulty = (1 / 2) * (4 / 1);
        $this->assertEquals($expectedDifficulty, $result['difficulty'], 'Difficulty calculation should be correct');

        $expectedEffort = $expectedDifficulty * $expectedVolume;
        $this->assertEquals($expectedEffort, $result['effort'], 'Effort calculation should be correct');
    }

    public function testCalculateMetricsWithComplexData(): void
    {
        $operators = ['+', '-', '*', '/', '=', '==', '!=', '+', '-'];
        $operands = ['$a', '$b', '$c', '$d', '$e', '$f', '$a', '$b', '$c', '$d', '$e'];
        $identifier = 'ComplexClass::complexMethod';

        $result = $this->calculator->calculateMetrics($operators, $operands, $identifier);

        // Check basic counts
        $this->assertEquals(7, $result['n1'], 'Should have 7 distinct operators');
        $this->assertEquals(6, $result['n2'], 'Should have 6 distinct operands'); // $a, $b, $c, $d, $e, $f
        $this->assertEquals(9, $result['N1'], 'Should have 9 total operators');
        $this->assertEquals(11, $result['N2'], 'Should have 11 total operands');

        // Check calculated metrics
        $this->assertEquals(20, $result['programLength'], 'Program length should be 20');
        $this->assertEquals(13, $result['programVocabulary'], 'Program vocabulary should be 13');

        // Check advanced metrics
        $expectedVolume = 20 * log(13, 2);
        $this->assertEquals($expectedVolume, $result['volume'], 'Volume calculation should be correct');

        $expectedDifficulty = (7 / 2) * (11 / 6);
        $this->assertEquals($expectedDifficulty, $result['difficulty'], 'Difficulty calculation should be correct');

        $expectedEffort = $expectedDifficulty * $expectedVolume;
        $this->assertEquals($expectedEffort, $result['effort'], 'Effort calculation should be correct');

        $this->assertEquals($identifier, $result['fqName'], 'Identifier should be preserved');
    }
}
