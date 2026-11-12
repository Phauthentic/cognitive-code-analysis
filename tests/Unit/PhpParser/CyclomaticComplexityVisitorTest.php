<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\PhpParser;

use Phauthentic\CognitiveCodeAnalysis\Business\Cyclomatic\CyclomaticComplexityCalculator;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\CyclomaticComplexityVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

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
        $calculator = new CyclomaticComplexityCalculator();
        $visitor = new CyclomaticComplexityVisitor($calculator);
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

    /**
     * @return array<string, array{string, int}>
     */
    public static function fixtureMethodProvider(): array
    {
        return [
            'and symbol' => ['both', 3],
            'and keyword' => ['keyword', 3],
            'or symbol' => ['orSymbol', 3],
            'or keyword' => ['orKeyword', 3],
            // 1 base + 1 switch + 2 case, default is not counted
            'switch default' => ['withDefault', 4],
        ];
    }

    #[DataProvider('fixtureMethodProvider')]
    public function testFixtureMethodComplexity(string $method, int $expected): void
    {
        $visitor = $this->visitFixture();

        $this->assertSame($expected, $visitor->getMethodComplexity()['\\Cyclomatic\\Cyc::' . $method]);
    }

    public function testDefaultArmIsReportedButNotCounted(): void
    {
        $breakdown = $this->visitFixture()->getMethodComplexityBreakdown()['\\Cyclomatic\\Cyc::withDefault'];

        $this->assertSame(1, $breakdown['default']);
        $this->assertSame(2, $breakdown['case']);
    }

    private function visitFixture(): CyclomaticComplexityVisitor
    {
        $ast = (new ParserFactory())->createForHostVersion()
            ->parse((string)file_get_contents(__DIR__ . '/../../Fixtures/Cyclomatic/Cyc.php'));
        $visitor = new CyclomaticComplexityVisitor(new CyclomaticComplexityCalculator());
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor;
    }
}
