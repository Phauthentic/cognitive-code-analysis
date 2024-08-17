<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\PhpParser;

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use Phauthentic\CodeQualityMetrics\PhpParser\CognitiveMetricsVisitor;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class CognitiveMetricsVisitorTest extends TestCase
{
    protected function getTestCode(): string
    {
        return <<<'CODE'
        <?php

        namespace MyNamespace;

        class MyClass {
            private $test = 1;

            public function myMethod($param1, $param2)
            {
                $var1 = 1;
                $var2 = 2;
                if ($var1 == $var2) {
                    return $var1;
                } elseif ($var2 > 0) {
                    return $var2;
                } else {
                    return 0;
                }
            }
        }
        CODE;
    }

    public function testMethodMetricsCalculation(): void
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($this->getTestCode());

        $traverser = new NodeTraverser();
        $metricsVisitor = new CognitiveMetricsVisitor();
        $traverser->addVisitor($metricsVisitor);

        $traverser->traverse($statements);

        $methodMetrics = $metricsVisitor->getMethodMetrics();
        $this->assertArrayHasKey('MyNamespace\\MyClass::myMethod', $methodMetrics);

        $metrics = $methodMetrics['MyNamespace\\MyClass::myMethod'];
        $this->assertEquals(12, $metrics['line_count']);
        $this->assertEquals(2, $metrics['arg_count']);
        $this->assertEquals(3, $metrics['return_count']);
        $this->assertEquals(2, $metrics['variable_count']);
        $this->assertEquals(0, $metrics['property_call_count']);
        $this->assertEquals(1, $metrics['if_count']);
        $this->assertEquals(1, $metrics['if_nesting_level']);
        $this->assertEquals(2, $metrics['else_count']);
    }
}
