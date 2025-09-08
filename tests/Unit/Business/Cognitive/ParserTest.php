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
}
