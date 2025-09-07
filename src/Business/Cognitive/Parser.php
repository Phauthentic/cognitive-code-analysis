<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\AnnotationVisitor;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\CognitiveMetricsVisitor;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\CyclomaticComplexityVisitor;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\HalsteadMetricsVisitor;
use PhpParser\NodeTraverserInterface;
use PhpParser\Parser as PhpParser;
use PhpParser\NodeTraverser;
use PhpParser\Error;
use PhpParser\ParserFactory;

/**
 *
 */
class Parser
{
    protected PhpParser $parser;
    protected AnnotationVisitor $annotationVisitor;
    protected CognitiveMetricsVisitor $cognitiveMetricsVisitor;
    protected CyclomaticComplexityVisitor $cyclomaticComplexityVisitor;
    protected HalsteadMetricsVisitor $halsteadMetricsVisitor;

    public function __construct(
        ParserFactory $parserFactory,
        protected readonly NodeTraverserInterface $traverser,
    ) {
        $this->parser = $parserFactory->createForHostVersion();

        // Create the annotation visitor but don't add it to the traverser
        // It will be used by other visitors to check for ignored items
        $this->annotationVisitor = new AnnotationVisitor();

        $this->cognitiveMetricsVisitor = new CognitiveMetricsVisitor();
        $this->cognitiveMetricsVisitor->setAnnotationVisitor($this->annotationVisitor);
        $this->traverser->addVisitor($this->cognitiveMetricsVisitor);

        $this->cyclomaticComplexityVisitor = new CyclomaticComplexityVisitor();
        $this->cyclomaticComplexityVisitor->setAnnotationVisitor($this->annotationVisitor);
        $this->traverser->addVisitor($this->cyclomaticComplexityVisitor);

        $this->halsteadMetricsVisitor = new HalsteadMetricsVisitor();
        $this->halsteadMetricsVisitor->setAnnotationVisitor($this->annotationVisitor);
        $this->traverser->addVisitor($this->halsteadMetricsVisitor);
    }

    /**
     * @return array<string, array<string, int>>
     * @throws CognitiveAnalysisException
     */
    public function parse(string $code): array
    {
        // First, scan for annotations to collect ignored items
        $this->scanForAnnotations($code);

        // Then parse for metrics
        $this->traverseAbstractSyntaxTree($code);

        $methodMetrics = $this->cognitiveMetricsVisitor->getMethodMetrics();
        $this->cognitiveMetricsVisitor->resetValues();

        $methodMetrics = $this->getCyclomaticComplexityVisitor($methodMetrics);
        $methodMetrics = $this->getHalsteadMetricsVisitor($methodMetrics);

        return $methodMetrics;
    }

    /**
     * Scan the code for annotations to collect ignored items.
     */
    private function scanForAnnotations(string $code): void
    {
        // Reset the annotation visitor state before scanning
        $this->annotationVisitor->reset();

        try {
            $ast = $this->parser->parse($code);
        } catch (Error $e) {
            throw new CognitiveAnalysisException("Parse error: {$e->getMessage()}", 0, $e);
        }

        if ($ast === null) {
            throw new CognitiveAnalysisException("Could not parse the code.");
        }

        // Create a temporary traverser just for annotations
        $annotationTraverser = new NodeTraverser();
        $annotationTraverser->addVisitor($this->annotationVisitor);
        $annotationTraverser->traverse($ast);
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

    /**
     * @param array<string, array<string, int>> $methodMetrics
     * @return array<string, array<string, int>>
     */
    private function getHalsteadMetricsVisitor(array $methodMetrics): array
    {
        $halstead = $this->halsteadMetricsVisitor->getMetrics();
        foreach ($halstead['methods'] as $method => $metrics) {
            // Skip ignored methods
            if ($this->annotationVisitor->isMethodIgnored($method)) {
                continue;
            }
            // Skip malformed method keys (ClassName::)
            if (str_ends_with($method, '::')) {
                continue;
            }
            $methodMetrics[$method]['halstead'] = $metrics;
        }

        return $methodMetrics;
    }

    /**
     * @param array<string, array<string, int>> $methodMetrics
     * @return array<string, array<string, int>>
     */
    private function getCyclomaticComplexityVisitor(array $methodMetrics): array
    {
        $cyclomatic = $this->cyclomaticComplexityVisitor->getComplexitySummary();
        foreach ($cyclomatic['methods'] as $method => $complexity) {
            // Skip ignored methods
            if ($this->annotationVisitor->isMethodIgnored($method)) {
                continue;
            }
            // Skip malformed method keys (ClassName::)
            if (str_ends_with($method, '::')) {
                continue;
            }
            $methodMetrics[$method]['cyclomatic_complexity'] = $complexity;
        }

        return $methodMetrics;
    }

    /**
     * Get all ignored classes and methods.
     *
     * @return array<string, array<string, string>> Array with 'classes' and 'methods' keys
     */
    public function getIgnored(): array
    {
        return $this->annotationVisitor->getIgnored();
    }

    /**
     * Get ignored classes.
     *
     * @return array<string, string> Array of ignored class FQCNs
     */
    public function getIgnoredClasses(): array
    {
        return $this->annotationVisitor->getIgnoredClasses();
    }

    /**
     * Get ignored methods.
     *
     * @return array<string, string> Array of ignored method keys (ClassName::methodName)
     */
    public function getIgnoredMethods(): array
    {
        return $this->annotationVisitor->getIgnoredMethods();
    }
}
