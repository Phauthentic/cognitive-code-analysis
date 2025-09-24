<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Parser;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\TestCase;

/**
 * Test for Parser class with annotation support.
 */
class ParserTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser(
            new ParserFactory(),
            new NodeTraverser()
        );
    }

    public function testSkipsIgnoredClass(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        /**
         * @cca-ignore
         */
        class MyClass {
            public function myMethod() {
                $var = 1;
                if ($var > 0) {
                    return $var;
                }
                return 0;
            }
        }
        CODE;

        $metrics = $this->parser->parse($code);

        // The class should be ignored, so no metrics should be returned
        $this->assertEmpty($metrics);

        // Check that the ignored items are tracked
        $ignored = $this->parser->getIgnored();
        $this->assertContains('\\MyNamespace\\MyClass', $ignored['classes']);
    }

    public function testSkipsIgnoredMethod(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        class MyClass {
            /**
             * @cca-ignore
             */
            public function ignoredMethod() {
                $var = 1;
                if ($var > 0) {
                    return $var;
                }
                return 0;
            }

            public function normalMethod() {
                $var = 2;
                return $var;
            }
        }
        CODE;

        $metrics = $this->parser->parse($code);

        // Only the normal method should have metrics
        $this->assertCount(1, $metrics);
        $this->assertArrayHasKey('\\MyNamespace\\MyClass::normalMethod', $metrics);
        $this->assertArrayNotHasKey('\\MyNamespace\\MyClass::ignoredMethod', $metrics);

        // Check that the ignored method is tracked
        $ignored = $this->parser->getIgnored();
        $this->assertContains('\\MyNamespace\\MyClass::ignoredMethod', $ignored['methods']);
        $this->assertEmpty($ignored['classes']);
    }

    public function testSkipsIgnoredClassAndMethod(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        /**
         * @cca-ignore
         */
        class IgnoredClass {
            public function method1() {
                $var = 1;
                return $var;
            }
        }

        class NormalClass {
            /**
             * @cca-ignore
             */
            public function ignoredMethod() {
                $var = 1;
                if ($var > 0) {
                    return $var;
                }
                return 0;
            }

            public function normalMethod() {
                $var = 2;
                return $var;
            }
        }
        CODE;

        $metrics = $this->parser->parse($code);

        // Only the normal method in NormalClass should have metrics
        $this->assertCount(1, $metrics);
        $this->assertArrayHasKey('\\MyNamespace\\NormalClass::normalMethod', $metrics);
        $this->assertArrayNotHasKey('\\MyNamespace\\NormalClass::ignoredMethod', $metrics);
        $this->assertArrayNotHasKey('\\MyNamespace\\IgnoredClass::method1', $metrics);

        // Check that both ignored items are tracked
        $ignored = $this->parser->getIgnored();
        $this->assertContains('\\MyNamespace\\IgnoredClass', $ignored['classes']);
        $this->assertContains('\\MyNamespace\\NormalClass::ignoredMethod', $ignored['methods']);
    }

    public function testNoAnnotations(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        class MyClass {
            public function myMethod() {
                $var = 1;
                return $var;
            }
        }
        CODE;

        $metrics = $this->parser->parse($code);

        // The method should have metrics
        $this->assertCount(1, $metrics);
        $this->assertArrayHasKey('\\MyNamespace\\MyClass::myMethod', $metrics);

        // No ignored items
        $ignored = $this->parser->getIgnored();
        $this->assertEmpty($ignored['classes']);
        $this->assertEmpty($ignored['methods']);
    }

    public function testInlineCommentAnnotations(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        // @cca-ignore
        class MyClass {
            // @cca-ignore
            public function myMethod() {
                $var = 1;
                return $var;
            }
        }
        CODE;

        $metrics = $this->parser->parse($code);

        // Both class and method should be ignored
        $this->assertEmpty($metrics);

        // Check that both ignored items are tracked
        $ignored = $this->parser->getIgnored();
        $this->assertContains('\\MyNamespace\\MyClass', $ignored['classes']);
        $this->assertContains('\\MyNamespace\\MyClass::myMethod', $ignored['methods']);
    }

    public function testGetIgnoredMethods(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        class MyClass {
            /**
             * @cca-ignore
             */
            public function ignoredMethod1() {
                return 1;
            }

            /**
             * @cca-ignore
             */
            public function ignoredMethod2() {
                return 2;
            }

            public function normalMethod() {
                return 3;
            }
        }
        CODE;

        $this->parser->parse($code);

        $ignoredMethods = $this->parser->getIgnoredMethods();
        $this->assertCount(2, $ignoredMethods);
        $this->assertContains('\\MyNamespace\\MyClass::ignoredMethod1', $ignoredMethods);
        $this->assertContains('\\MyNamespace\\MyClass::ignoredMethod2', $ignoredMethods);
        $this->assertNotContains('\\MyNamespace\\MyClass::normalMethod', $ignoredMethods);
    }

    public function testGetIgnoredClasses(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        /**
         * @cca-ignore
         */
        class IgnoredClass1 {
        }

        /**
         * @cca-ignore
         */
        class IgnoredClass2 {
        }

        class NormalClass {
        }
        CODE;

        $this->parser->parse($code);

        $ignoredClasses = $this->parser->getIgnoredClasses();
        $this->assertCount(2, $ignoredClasses);
        $this->assertContains('\\MyNamespace\\IgnoredClass1', $ignoredClasses);
        $this->assertContains('\\MyNamespace\\IgnoredClass2', $ignoredClasses);
        $this->assertNotContains('\\MyNamespace\\NormalClass', $ignoredClasses);
    }

    /**
     * Test that anonymous classes are properly handled and don't cause missing keys errors.
     * This test reproduces the issue that was fixed where anonymous classes would cause
     * "Missing required keys" errors in CognitiveMetrics constructor.
     */
    public function testHandlesAnonymousClassesCorrectly(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        class TestClass {
            public function testMethod() {
                $subscriber = new class(123) extends BaseClass {
                    public function __construct(private int $value) {
                        $this->value = $value;
                    }

                    public function process() {
                        if ($this->value > 0) {
                            return $this->value;
                        }
                        return 0;
                    }
                };

                $anotherSubscriber = new class(456) extends AnotherBaseClass {
                    public function __construct(private int $value) {
                        $this->value = $value;
                    }

                    public function handle() {
                        return $this->value * 2;
                    }
                };

                return $subscriber->process();
            }

            public function normalMethod() {
                $var = 1;
                return $var;
            }
        }
        CODE;

        // This should not throw any exceptions
        $metrics = $this->parser->parse($code);

        // Only the methods from the named class should have metrics
        // Anonymous class methods should be ignored
        $this->assertCount(2, $metrics);
        $this->assertArrayHasKey('\\MyNamespace\\TestClass::testMethod', $metrics);
        $this->assertArrayHasKey('\\MyNamespace\\TestClass::normalMethod', $metrics);

        // Verify that anonymous class methods are NOT present
        $this->assertArrayNotHasKey('\\MyNamespace\\TestClass::__construct', $metrics);
        $this->assertArrayNotHasKey('\\MyNamespace\\TestClass::process', $metrics);
        $this->assertArrayNotHasKey('\\MyNamespace\\TestClass::handle', $metrics);

        // Verify that the metrics have all required keys
        foreach ($metrics as $methodKey => $methodMetrics) {
            $this->assertArrayHasKey('lineCount', $methodMetrics, "Method {$methodKey} missing lineCount");
            $this->assertArrayHasKey('argCount', $methodMetrics, "Method {$methodKey} missing argCount");
            $this->assertArrayHasKey('returnCount', $methodMetrics, "Method {$methodKey} missing returnCount");
            $this->assertArrayHasKey('variableCount', $methodMetrics, "Method {$methodKey} missing variableCount");
            $this->assertArrayHasKey('propertyCallCount', $methodMetrics, "Method {$methodKey} missing propertyCallCount");
            $this->assertArrayHasKey('ifCount', $methodMetrics, "Method {$methodKey} missing ifCount");
            $this->assertArrayHasKey('ifNestingLevel', $methodMetrics, "Method {$methodKey} missing ifNestingLevel");
            $this->assertArrayHasKey('elseCount', $methodMetrics, "Method {$methodKey} missing elseCount");
        }

        // Verify specific metrics for testMethod
        $testMethodMetrics = $metrics['\\MyNamespace\\TestClass::testMethod'];
        $this->assertGreaterThan(0, $testMethodMetrics['lineCount']);
        $this->assertEquals(0, $testMethodMetrics['argCount']);
        $this->assertGreaterThan(0, $testMethodMetrics['returnCount']);

        // Verify specific metrics for normalMethod
        $normalMethodMetrics = $metrics['\\MyNamespace\\TestClass::normalMethod'];
        $this->assertGreaterThan(0, $normalMethodMetrics['lineCount']);
        $this->assertEquals(0, $normalMethodMetrics['argCount']);
        $this->assertEquals(1, $normalMethodMetrics['returnCount']);
        $this->assertEquals(1, $normalMethodMetrics['variableCount']);
    }

    /**
     * Test that the parser handles complex anonymous class scenarios without errors.
     * This test ensures that the fix for anonymous classes works in various scenarios.
     */
    public function testHandlesComplexAnonymousClassScenarios(): void
    {
        $code = <<<'CODE'
        <?php

        namespace TestNamespace;

        class MainClass {
            public function createAnonymousObjects() {
                // Anonymous class with constructor
                $obj1 = new class(42) {
                    public function __construct(private int $value) {
                        $this->value = $value;
                    }

                    public function getValue() {
                        return $this->value;
                    }
                };

                // Anonymous class extending another class
                $obj2 = new class extends BaseClass {
                    public function __construct() {
                        parent::__construct();
                    }

                    public function process() {
                        if (true) {
                            return 'processed';
                        }
                        return 'not processed';
                    }
                };

                // Anonymous class implementing interface
                $obj3 = new class implements SomeInterface {
                    public function __construct() {
                        $this->initialize();
                    }

                    public function initialize() {
                        $var = 1;
                        return $var;
                    }

                    public function execute() {
                        return 'executed';
                    }
                };

                return $obj1->getValue();
            }

            public function simpleMethod() {
                return 'simple';
            }
        }
        CODE;

        // This should not throw any exceptions
        $metrics = $this->parser->parse($code);

        // Only methods from the named class should be present
        $this->assertCount(2, $metrics);
        $this->assertArrayHasKey('\\TestNamespace\\MainClass::createAnonymousObjects', $metrics);
        $this->assertArrayHasKey('\\TestNamespace\\MainClass::simpleMethod', $metrics);

        // Verify all metrics have required keys
        foreach ($metrics as $methodKey => $methodMetrics) {
            $requiredKeys = [
                'lineCount', 'argCount', 'returnCount', 'variableCount',
                'propertyCallCount', 'ifCount', 'ifNestingLevel', 'elseCount'
            ];

            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $methodMetrics, "Method {$methodKey} missing required key: {$key}");
                $this->assertIsInt($methodMetrics[$key], "Method {$methodKey} key {$key} should be an integer");
            }
        }
    }
}
