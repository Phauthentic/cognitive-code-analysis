<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\PhpParser;

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class CyclomaticComplexityVisitorTest extends TestCase
{
    public function testCyclomaticComplexityCalculation(): void
    {
        $code = <<<'CODE'
        <?php
        namespace MyNamespace;
        class MyClass {
            public function foo($a) {
                if ($a > 0) {
                    for ($i = 0; $i < $a; $i++) {
                        if ($i % 2 == 0) {
                            continue;
                        }
                    }
                } else {
                    while ($a < 10) {
                        $a++;
                    }
                }
            }
        }
        CODE;

        $parser = (new ParserFactory())->createForHostVersion();
        $ast = $parser->parse($code);
        $traverser = new NodeTraverser();
        $visitor = new \Phauthentic\CognitiveCodeAnalysis\PhpParser\CyclomaticComplexityVisitor();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);
        $classKey = '\\MyNamespace\\MyClass';
        $methodKey = '\\MyNamespace\\MyClass::foo';
        $classComplexity = $visitor->getClassComplexity();
        $methodComplexity = $visitor->getMethodComplexity();
        $this->assertArrayHasKey($classKey, $classComplexity);
        $this->assertArrayHasKey($methodKey, $methodComplexity);
        // The method has: 1 (base) + 1 (if) + 1 (for) + 1 (if) + 1 (while) = 5
        $this->assertEquals(5, $methodComplexity[$methodKey]);
        // The class complexity should be at least the sum of its methods (here, just one method)
        $this->assertEquals(6, $classComplexity[$classKey]);
    }
}