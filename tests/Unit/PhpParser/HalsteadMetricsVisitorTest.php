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
}
