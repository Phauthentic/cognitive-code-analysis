<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\PhpParser;

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\CognitiveMetricsVisitor;
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
        $this->assertArrayHasKey('\\MyNamespace\\MyClass::myMethod', $methodMetrics);

        $metrics = $methodMetrics['\\MyNamespace\\MyClass::myMethod'];
        $this->assertEquals(8, $metrics['line']);
        $this->assertEquals(12, $metrics['lineCount']);
        $this->assertEquals(2, $metrics['argCount']);
        $this->assertEquals(3, $metrics['returnCount']);
        $this->assertEquals(2, $metrics['variableCount']);
        $this->assertEquals(0, $metrics['propertyCallCount']);
        $this->assertEquals(1, $metrics['ifCount']);
        $this->assertEquals(1, $metrics['ifNestingLevel']);
        $this->assertEquals(2, $metrics['elseCount']);
    }

    public function testCountVariablesNotAlreadyTrackedAsArguments(): void
    {
        $code = <<<'CODE'
        <?php
        class Example {
            public function foo($arg1) {
                $a = 1;
                $b = 2;
                $arg1 = 3;
                ${$dynamic} = 4;
            }
        }
        CODE;

//$code = file_get_contents(__DIR__ . '/../../TestCode/Paginator.php');

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $visitor = new CognitiveMetricsVisitor();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $metrics = $visitor->getMethodMetrics();
        //dd($metrics);
        $method = '\Example::foo';

        $this->assertArrayHasKey($method, $metrics);
        $this->assertEquals(3, $metrics[$method]['variableCount']);
    }

    /**
     * Test that anonymous classes are properly skipped and don't cause issues.
     * This test ensures that the CognitiveMetricsVisitor correctly handles
     * anonymous classes without creating incomplete metrics.
     */
    public function testSkipsAnonymousClasses(): void
    {
        $code = <<<'CODE'
        <?php

        namespace TestNamespace;

        class TestClass {
            public function methodWithAnonymousClass() {
                $anonymous = new class(42) {
                    public function __construct(private int $value) {
                        $this->value = $value;
                    }

                    public function getValue() {
                        return $this->value;
                    }
                };

                return $anonymous->getValue();
            }

            public function normalMethod() {
                $var = 1;
                return $var;
            }
        }
        CODE;

        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $traverser = new NodeTraverser();
        $metricsVisitor = new CognitiveMetricsVisitor();
        $traverser->addVisitor($metricsVisitor);

        $traverser->traverse($statements);

        $methodMetrics = $metricsVisitor->getMethodMetrics();

        // Only methods from the named class should be present
        $this->assertCount(2, $methodMetrics);
        $this->assertArrayHasKey('\\TestNamespace\\TestClass::methodWithAnonymousClass', $methodMetrics);
        $this->assertArrayHasKey('\\TestNamespace\\TestClass::normalMethod', $methodMetrics);

        // Verify that all metrics have the required keys
        foreach ($methodMetrics as $methodKey => $metrics) {
            $requiredKeys = [
                'line', 'lineCount', 'argCount', 'returnCount', 'variableCount',
                'propertyCallCount', 'ifCount', 'ifNestingLevel', 'elseCount'
            ];

            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $metrics, "Method {$methodKey} missing required key: {$key}");
                $this->assertIsInt($metrics[$key], "Method {$methodKey} key {$key} should be an integer");
            }
        }

        // Verify specific metrics
        $methodWithAnonymousMetrics = $methodMetrics['\\TestNamespace\\TestClass::methodWithAnonymousClass'];
        $this->assertGreaterThan(0, $methodWithAnonymousMetrics['lineCount']);
        $this->assertEquals(0, $methodWithAnonymousMetrics['argCount']);
        $this->assertGreaterThan(0, $methodWithAnonymousMetrics['returnCount']);

        $normalMethodMetrics = $methodMetrics['\\TestNamespace\\TestClass::normalMethod'];
        $this->assertGreaterThan(0, $normalMethodMetrics['lineCount']);
        $this->assertEquals(0, $normalMethodMetrics['argCount']);
        $this->assertEquals(1, $normalMethodMetrics['returnCount']);
        $this->assertEquals(1, $normalMethodMetrics['variableCount']);
    }

    /**
     * Test that the visitor handles multiple anonymous classes correctly.
     * This test ensures that the fix works when there are multiple anonymous
     * classes in the same method or file.
     */
    public function testHandlesMultipleAnonymousClasses(): void
    {
        $code = <<<'CODE'
        <?php

        namespace TestNamespace;

        class TestClass {
            public function methodWithMultipleAnonymousClasses() {
                $obj1 = new class(1) {
                    public function __construct(private int $value) {
                        $this->value = $value;
                    }
                };

                $obj2 = new class(2) {
                    public function __construct(private int $value) {
                        $this->value = $value;
                    }
                };

                $obj3 = new class(3) {
                    public function __construct(private int $value) {
                        $this->value = $value;
                    }
                };

                return $obj1->value + $obj2->value + $obj3->value;
            }
        }
        CODE;

        $parser = (new ParserFactory())->createForHostVersion();
        $statements = $parser->parse($code);

        $traverser = new NodeTraverser();
        $metricsVisitor = new CognitiveMetricsVisitor();
        $traverser->addVisitor($metricsVisitor);

        $traverser->traverse($statements);

        $methodMetrics = $metricsVisitor->getMethodMetrics();

        // Only the method from the named class should be present
        $this->assertCount(1, $methodMetrics);
        $this->assertArrayHasKey('\\TestNamespace\\TestClass::methodWithMultipleAnonymousClasses', $methodMetrics);

        // Verify that the metrics have all required keys
        $metrics = $methodMetrics['\\TestNamespace\\TestClass::methodWithMultipleAnonymousClasses'];
        $requiredKeys = [
            'line', 'lineCount', 'argCount', 'returnCount', 'variableCount',
            'propertyCallCount', 'ifCount', 'ifNestingLevel', 'elseCount'
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $metrics, "Missing required key: {$key}");
            $this->assertIsInt($metrics[$key], "Key {$key} should be an integer");
        }
    }
}
