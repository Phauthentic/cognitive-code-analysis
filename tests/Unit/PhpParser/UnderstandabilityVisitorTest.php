<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\PhpParser;

use Phauthentic\CognitiveCodeAnalysis\Business\Understandability\UnderstandabilityCalculator;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\AnnotationVisitor;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\UnderstandabilityVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class UnderstandabilityVisitorTest extends TestCase
{
    private function analyzeMethod(string $code, string $methodName = 'run'): int
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $ast = $parser->parse($code);
        $calculator = new UnderstandabilityCalculator();
        $visitor = new UnderstandabilityVisitor($calculator);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $methodKey = '\\MyNamespace\\MyClass::' . $methodName;
        $complexity = $visitor->getMethodComplexity();

        $this->assertArrayHasKey($methodKey, $complexity, 'Method not found: ' . $methodKey);

        return $complexity[$methodKey];
    }

    public function testSwitchOnlyScoresOneLikeGetWords(): void
    {
        $code = <<<'CODE'
        <?php
        namespace MyNamespace;
        class MyClass {
            public function getWords(int $number): string {
                switch ($number) {
                    case 1: return 'one';
                    case 2: return 'a couple';
                    case 3: return 'a few';
                    default: return 'lots';
                }
            }
        }
        CODE;

        $this->assertSame(1, $this->analyzeMethod($code, 'getWords'));
    }

    public function testNestedLoopsAndContinueMatchSumOfPrimes(): void
    {
        $code = <<<'CODE'
        <?php
        namespace MyNamespace;
        class MyClass {
            public function sumOfPrimes(int $max): int {
                $total = 0;
                for ($i = 1; $i <= $max; $i++) {
                    for ($j = 2; $j < $i; $j++) {
                        if ($i % $j == 0) {
                            continue 2;
                        }
                    }
                    $total += $i;
                }
                return $total;
            }
        }
        CODE;

        $this->assertSame(7, $this->analyzeMethod($code, 'sumOfPrimes'));
    }

    public function testNestedIfForWhileCatchScoresNine(): void
    {
        $code = <<<'CODE'
        <?php
        namespace MyNamespace;
        class MyClass {
            public function run(): void {
                try {
                    if ($condition1) {
                        for ($i = 0; $i < 10; $i++) {
                            while ($condition2) {
                            }
                        }
                    }
                } catch (\Exception $e) {
                    if ($condition2) {
                    }
                }
            }
        }
        CODE;

        $this->assertSame(9, $this->analyzeMethod($code));
    }

    public function testClosureNestingScoresTwo(): void
    {
        $code = <<<'CODE'
        <?php
        namespace MyNamespace;
        class MyClass {
            public function run(): void {
                $r = fn() => $condition1 ? 1 : 0;
            }
        }
        CODE;

        // arrow fn with ternary inside: closure nesting + ternary structural (+1+1)
        $this->assertSame(2, $this->analyzeMethod($code));
    }

    public function testIfInsideClosureScoresTwo(): void
    {
        $code = <<<'CODE'
        <?php
        namespace MyNamespace;
        class MyClass {
            public function run(): void {
                $r = function () {
                    if ($condition1) {
                    }
                };
            }
        }
        CODE;

        $this->assertSame(2, $this->analyzeMethod($code));
    }

    public function testLogicalOperatorSequencesInCondition(): void
    {
        $code = <<<'CODE'
        <?php
        namespace MyNamespace;
        class MyClass {
            public function run(): void {
                if ($a && $b && $c) {
                }
            }
        }
        CODE;

        // if +1, one && sequence +1
        $this->assertSame(2, $this->analyzeMethod($code));
    }

    public function testMixedLogicalOperatorsIncrementPerSequence(): void
    {
        $code = <<<'CODE'
        <?php
        namespace MyNamespace;
        class MyClass {
            public function run(): void {
                if ($a && $b || $c && $d) {
                }
            }
        }
        CODE;

        // if +1, && +1, || +1, && +1 = 4
        $this->assertSame(4, $this->analyzeMethod($code));
    }

    public function testSwitchLowerThanIfElseIfChain(): void
    {
        $switchCode = <<<'CODE'
        <?php
        namespace MyNamespace;
        class MyClass {
            public function viaSwitch(int $n): string {
                switch ($n) {
                    case 1: return 'a';
                    case 2: return 'b';
                    default: return 'c';
                }
            }
        }
        CODE;

        $ifCode = <<<'CODE'
        <?php
        namespace MyNamespace;
        class MyClass {
            public function viaIf(int $n): string {
                if ($n === 1) {
                    return 'a';
                } elseif ($n === 2) {
                    return 'b';
                } else {
                    return 'c';
                }
            }
        }
        CODE;

        $switchScore = $this->analyzeMethod($switchCode, 'viaSwitch');
        $ifScore = $this->analyzeMethod($ifCode, 'viaIf');

        $this->assertLessThan($ifScore, $switchScore);
        $this->assertSame(1, $switchScore);
    }

    public function testDirectRecursionAddsFundamentalIncrement(): void
    {
        $code = <<<'CODE'
        <?php
        namespace MyNamespace;
        class MyClass {
            public function run(int $n): int {
                if ($n <= 0) {
                    return 0;
                }
                return $this->run($n - 1);
            }
        }
        CODE;

        // if +1, recursion +1
        $this->assertSame(2, $this->analyzeMethod($code));
    }

    public function testIgnoredMethodIsSkippedWhenAnnotationVisitorWired(): void
    {
        $code = <<<'CODE'
        <?php
        namespace MyNamespace;
        class MyClass {
            /** @cca-ignore */
            public function run(): void {
                if (true) {
                    for ($i = 0; $i < 10; $i++) {}
                }
            }
        }
        CODE;

        $parser = (new ParserFactory())->createForHostVersion();
        $ast = $parser->parse($code);

        $annotationVisitor = new AnnotationVisitor();
        $annotationTraverser = new NodeTraverser();
        $annotationTraverser->addVisitor($annotationVisitor);
        $annotationTraverser->traverse($ast);

        $visitor = new UnderstandabilityVisitor(new UnderstandabilityCalculator());
        $visitor->setAnnotationVisitor($annotationVisitor);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $this->assertEmpty($visitor->getMethodComplexity());
    }
}
