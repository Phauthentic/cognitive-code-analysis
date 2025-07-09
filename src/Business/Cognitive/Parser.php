<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\CognitiveMetricsVisitor;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\CyclomaticComplexityVisitor;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\HalsteadMetricsVisitor;
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
    protected CognitiveMetricsVisitor $cognitiveMetricsVisitor;
    protected CyclomaticComplexityVisitor $cyclomaticComplexityVisitor;
    protected HalsteadMetricsVisitor $halsteadMetricsVisitor;

    public function __construct(
        ParserFactory $parserFactory,
        protected readonly NodeTraverserInterface $traverser,
    ) {
        $this->parser = $parserFactory->createForHostVersion();

        $this->cognitiveMetricsVisitor = new CognitiveMetricsVisitor();
        $this->traverser->addVisitor($this->cognitiveMetricsVisitor);

        $this->cyclomaticComplexityVisitor = new CyclomaticComplexityVisitor();
        $this->traverser->addVisitor($this->cyclomaticComplexityVisitor);

        $this->halsteadMetricsVisitor = new HalsteadMetricsVisitor();
        $this->traverser->addVisitor($this->halsteadMetricsVisitor);
    }

    /**
     * @return array<string, array<string, int>>
     * @throws CognitiveAnalysisException
     */
    public function parse(string $code): array
    {
        $this->traverseAbstractSyntaxTree($code);

        $methodMetrics = $this->cognitiveMetricsVisitor->getMethodMetrics();
        $this->cognitiveMetricsVisitor->resetValues();

        $cyclomatic = $this->cyclomaticComplexityVisitor->getComplexitySummary();
        foreach ($cyclomatic['methods'] as $method => $complexity) {
            $methodMetrics[$method]['cyclomatic_complexity'] = $complexity;
        }

        $halstead = $this->halsteadMetricsVisitor->getMetrics();
//dd(array_keys($methodMetrics));
//dd(array_keys($halstead['methods']));
//dd(array_diff(array_keys($halstead['methods']), array_keys($methodMetrics)));
        foreach ($halstead['methods'] as $method => $metrics) {
            if (!isset( $methodMetrics[$method])) {
                //dd( $methodMetrics[$method]);
                //dd($method);
            }
            $methodMetrics[$method]['halstead'] = $metrics;
        }

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
