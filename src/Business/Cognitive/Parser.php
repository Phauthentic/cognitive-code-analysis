<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\CognitiveMetricsVisitor;
use PhpParser\NodeTraverserInterface;
use PhpParser\Parser as PhpParser;
use PhpParser\Error;
use PhpParser\ParserFactory;

/**
 *
 */
class Parser
{
    protected PhpParser $parser;
    protected CognitiveMetricsVisitor $visitor;

    public function __construct(
        ParserFactory $parserFactory,
        protected readonly NodeTraverserInterface $traverser,
    ) {
        $this->parser = $parserFactory->createForHostVersion();
        $this->visitor = new CognitiveMetricsVisitor();
        $this->traverser->addVisitor($this->visitor);
    }

    /**
     * @return array<string, array<string, int>>
     * @throws CognitiveAnalysisException
     */
    public function parse(string $code): array
    {
        $this->traverseAbstractSyntaxTree($code);

        $methodMetrics = $this->visitor->getMethodMetrics();
        $this->visitor->resetValues();

        return $methodMetrics;
    }

    /**
     * @throws CognitiveAnalysisException
     */
    private function traverseAbstractSyntaxTree(string $code): void
    {
        try {
            $ast = $this->parser->parse($code);
        } catch (Error $e) {
            throw new CognitiveAnalysisException("Parse error: {$e->getMessage()}", 0, $e);
        }

        if ($ast === null) {
            throw new CognitiveAnalysisException("Could not parse the code.");
        }

        $this->traverser->traverse($ast);
    }
}
