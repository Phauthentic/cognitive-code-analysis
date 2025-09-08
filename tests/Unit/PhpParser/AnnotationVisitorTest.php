<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\PhpParser;

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\AnnotationVisitor;
use PHPUnit\Framework\TestCase;

/**
 * Test for AnnotationVisitor class.
 */
class AnnotationVisitorTest extends TestCase
{
    private AnnotationVisitor $visitor;
    private $parser;
    private NodeTraverser $traverser;

    protected function setUp(): void
    {
        $this->visitor = new AnnotationVisitor();
        $this->parser = (new ParserFactory())->createForHostVersion();
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor($this->visitor);
    }

    public function testDetectsIgnoredClass(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        /**
         * @cca-ignore
         */
        class MyClass {
            public function myMethod() {
                // This method should be ignored because the class is ignored
            }
        }
        CODE;

        $statements = $this->parser->parse($code);
        $this->traverser->traverse($statements);

        $ignored = $this->visitor->getIgnored();

        $this->assertArrayHasKey('classes', $ignored);
        $this->assertArrayHasKey('methods', $ignored);
        $this->assertContains('\\MyNamespace\\MyClass', $ignored['classes']);
        $this->assertEmpty($ignored['methods']); // Methods in ignored classes are not tracked
    }

    public function testDetectsIgnoredMethod(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        class MyClass {
            /**
             * @cca-ignore
             */
            public function ignoredMethod() {
                // This method should be ignored
            }

            public function normalMethod() {
                // This method should not be ignored
            }
        }
        CODE;

        $statements = $this->parser->parse($code);
        $this->traverser->traverse($statements);

        $ignored = $this->visitor->getIgnored();

        $this->assertArrayHasKey('classes', $ignored);
        $this->assertArrayHasKey('methods', $ignored);
        $this->assertEmpty($ignored['classes']);
        $this->assertContains('\\MyNamespace\\MyClass::ignoredMethod', $ignored['methods']);
        $this->assertNotContains('\\MyNamespace\\MyClass::normalMethod', $ignored['methods']);
    }

    public function testDetectsIgnoredClassAndMethod(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        /**
         * @cca-ignore
         */
        class IgnoredClass {
            public function method1() {
                // This method should be ignored because the class is ignored
            }
        }

        class NormalClass {
            /**
             * @cca-ignore
             */
            public function ignoredMethod() {
                // This method should be ignored
            }

            public function normalMethod() {
                // This method should not be ignored
            }
        }
        CODE;

        $statements = $this->parser->parse($code);
        $this->traverser->traverse($statements);

        $ignored = $this->visitor->getIgnored();

        $this->assertArrayHasKey('classes', $ignored);
        $this->assertArrayHasKey('methods', $ignored);
        $this->assertContains('\\MyNamespace\\IgnoredClass', $ignored['classes']);
        $this->assertContains('\\MyNamespace\\NormalClass::ignoredMethod', $ignored['methods']);
        $this->assertNotContains('\\MyNamespace\\NormalClass::normalMethod', $ignored['methods']);
    }

    public function testDetectsInlineCommentAnnotation(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        // @cca-ignore
        class MyClass {
            // @cca-ignore
            public function myMethod() {
                // This method should be ignored
            }
        }
        CODE;

        $statements = $this->parser->parse($code);
        $this->traverser->traverse($statements);

        $ignored = $this->visitor->getIgnored();

        $this->assertArrayHasKey('classes', $ignored);
        $this->assertArrayHasKey('methods', $ignored);
        $this->assertContains('\\MyNamespace\\MyClass', $ignored['classes']);
        $this->assertContains('\\MyNamespace\\MyClass::myMethod', $ignored['methods']);
    }

    public function testDetectsTraitAnnotation(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        /**
         * @cca-ignore
         */
        trait MyTrait {
            public function myMethod() {
                // This method should be ignored because the trait is ignored
            }
        }
        CODE;

        $statements = $this->parser->parse($code);
        $this->traverser->traverse($statements);

        $ignored = $this->visitor->getIgnored();

        $this->assertArrayHasKey('classes', $ignored);
        $this->assertArrayHasKey('methods', $ignored);
        $this->assertContains('\\MyNamespace\\MyTrait', $ignored['classes']);
        $this->assertEmpty($ignored['methods']);
    }

    public function testNoAnnotations(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        class MyClass {
            public function myMethod() {
                // No annotations
            }
        }
        CODE;

        $statements = $this->parser->parse($code);
        $this->traverser->traverse($statements);

        $ignored = $this->visitor->getIgnored();

        $this->assertArrayHasKey('classes', $ignored);
        $this->assertArrayHasKey('methods', $ignored);
        $this->assertEmpty($ignored['classes']);
        $this->assertEmpty($ignored['methods']);
    }

    public function testResetFunctionality(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        /**
         * @cca-ignore
         */
        class MyClass {
            public function myMethod() {
                // This should be ignored
            }
        }
        CODE;

        $statements = $this->parser->parse($code);
        $this->traverser->traverse($statements);

        // Verify items are detected
        $ignored = $this->visitor->getIgnored();
        $this->assertNotEmpty($ignored['classes']);

        // Reset and verify items are cleared
        $this->visitor->reset();
        $ignored = $this->visitor->getIgnored();
        $this->assertEmpty($ignored['classes']);
        $this->assertEmpty($ignored['methods']);
    }

    public function testIsClassIgnored(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        /**
         * @cca-ignore
         */
        class MyClass {
        }
        CODE;

        $statements = $this->parser->parse($code);
        $this->traverser->traverse($statements);

        $this->assertTrue($this->visitor->isClassIgnored('\\MyNamespace\\MyClass'));
        $this->assertFalse($this->visitor->isClassIgnored('\\MyNamespace\\OtherClass'));
    }

    public function testIsMethodIgnored(): void
    {
        $code = <<<'CODE'
        <?php

        namespace MyNamespace;

        class MyClass {
            /**
             * @cca-ignore
             */
            public function ignoredMethod() {
            }

            public function normalMethod() {
            }
        }
        CODE;

        $statements = $this->parser->parse($code);
        $this->traverser->traverse($statements);

        $this->assertTrue($this->visitor->isMethodIgnored('\\MyNamespace\\MyClass::ignoredMethod'));
        $this->assertFalse($this->visitor->isMethodIgnored('\\MyNamespace\\MyClass::normalMethod'));
    }
}
