<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\PhpParser;

use Phauthentic\CognitiveCodeAnalysis\Business\Halstead\HalsteadMetricsCalculator;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\HalsteadMetricsVisitor;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\TestCase;

class HalsteadMetricsVisitorTest extends TestCase
{
    public function testHalsteadMetricsCalculation(): void
    {
        $code = <<<'CODE'
        <?php
        namespace MyNamespace;
        class MyClass {
            public function add($a, $b) {
                $c = $a + $b;
                return $c;
            }
        }
        CODE;

        $parser = (new ParserFactory())->createForHostVersion();
        $ast = $parser->parse($code);
        $traverser = new NodeTraverser();
        $calculator = new HalsteadMetricsCalculator();
        $visitor = new HalsteadMetricsVisitor($calculator);
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);
        $metrics = $visitor->getMetrics();

        $classKey = '\\MyNamespace\\MyClass';
        $methodKey = '\\MyNamespace\\MyClass::add';
        $this->assertArrayHasKey('classes', $metrics);
        $this->assertArrayHasKey('methods', $metrics);
        $this->assertArrayHasKey($classKey, $metrics['classes']);
        $this->assertArrayHasKey($methodKey, $metrics['methods']);
        $classMetrics = $metrics['classes'][$classKey];
        $methodMetrics = $metrics['methods'][$methodKey];
        // Check that the metrics are as expected (basic checks)
        $this->assertGreaterThan(0, $classMetrics['n1']);
        $this->assertGreaterThan(0, $classMetrics['n2']);
        $this->assertGreaterThan(0, $classMetrics['N1']);
        $this->assertGreaterThan(0, $classMetrics['N2']);
        $this->assertGreaterThan(0, $methodMetrics['n1']);
        $this->assertGreaterThan(0, $methodMetrics['n2']);
        $this->assertGreaterThan(0, $methodMetrics['N1']);
        $this->assertGreaterThan(0, $methodMetrics['N2']);
    }

    public function testExactMetricsWithVariableVariable(): void
    {
        $code = <<<'CODE'
        <?php
        class MyClass {
            public function f() {
                $$name = 1;
                return $a + 2;
            }
        }
        CODE;

        $parser = (new ParserFactory())->createForHostVersion();
        $traverser = new NodeTraverser();
        $visitor = new HalsteadMetricsVisitor(new HalsteadMetricsCalculator());
        $traverser->addVisitor($visitor);
        $traverser->traverse($parser->parse($code));

        $metrics = $visitor->getMetrics()['methods']['\\MyClass::f'];

        // Operators: Assign, BinaryOp_Plus. Operands: $name (inner part of $$name), 1, $a, 2.
        $this->assertSame(2, $metrics['n1']);
        $this->assertSame(4, $metrics['n2']);
        $this->assertSame(2, $metrics['N1']);
        $this->assertSame(4, $metrics['N2']);
        $this->assertSame(6, $metrics['programLength']);
        $this->assertSame(6, $metrics['programVocabulary']);
        // V = N * log2(n) = 6 * log2(6)
        $this->assertEqualsWithDelta(15.509775, $metrics['volume'], 0.000001);
        // D = (n1 / 2) * (N2 / n2) = 1 * 1
        $this->assertEqualsWithDelta(1.0, $metrics['difficulty'], 0.000001);
        // E = D * V
        $this->assertEqualsWithDelta(15.509775, $metrics['effort'], 0.000001);
    }
}
