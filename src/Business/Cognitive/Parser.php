<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\AnnotationVisitor;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\CognitiveMetricsVisitor;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\CyclomaticComplexityVisitor;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\HalsteadMetricsVisitor;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\CombinedMetricsVisitor;
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
    protected CombinedMetricsVisitor $combinedVisitor;

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

        // Create the combined visitor for performance optimization
        $this->combinedVisitor = new CombinedMetricsVisitor();
        $this->combinedVisitor->setAnnotationVisitor();
    }

    /**
     * @return array<string, array<string, int>>
     * @throws CognitiveAnalysisException
     */
    public function parse(string $code): array
    {
        // First, scan for annotations to collect ignored items
        $this->scanForAnnotations($code);

        // Then parse for metrics using the combined visitor for better performance
        $this->traverseAbstractSyntaxTreeWithCombinedVisitor($code);

        $methodMetrics = $this->combinedVisitor->getMethodMetrics();
        $this->combinedVisitor->resetAll();

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
     * Traverse the AST using the combined visitor for better performance.
     * @throws CognitiveAnalysisException
     */
    private function traverseAbstractSyntaxTreeWithCombinedVisitor(string $code): void
    {
        try {
            $ast = $this->parser->parse($code);
        } catch (Error $e) {
            throw new CognitiveAnalysisException("Parse error: {$e->getMessage()}", 0, $e);
        }

        if ($ast === null) {
            throw new CognitiveAnalysisException("Could not parse the code.");
        }

        // Create a new traverser for the combined visitor
        $combinedTraverser = new NodeTraverser();
        $combinedTraverser->addVisitor($this->combinedVisitor);
        $combinedTraverser->traverse($ast);
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
            // Only add Halstead metrics to methods that were processed by CognitiveMetricsVisitor
            if (isset($methodMetrics[$method])) {
                $methodMetrics[$method]['halstead'] = $metrics;
            }
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
            // Only add cyclomatic complexity to methods that were processed by CognitiveMetricsVisitor
            if (isset($methodMetrics[$method])) {
                $methodMetrics[$method]['cyclomatic_complexity'] = $complexity;
            }
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

    /**
     * Clear static caches to prevent memory leaks during long-running processes.
     */
    public function clearStaticCaches(): void
    {
        // Clear FQCN caches from all visitors
        $this->clearStaticProperty('Phauthentic\CognitiveCodeAnalysis\PhpParser\CognitiveMetricsVisitor', 'fqcnCache');
        $this->clearStaticProperty('Phauthentic\CognitiveCodeAnalysis\PhpParser\CyclomaticComplexityVisitor', 'fqcnCache');
        $this->clearStaticProperty('Phauthentic\CognitiveCodeAnalysis\PhpParser\HalsteadMetricsVisitor', 'fqcnCache');
        $this->clearStaticProperty('Phauthentic\CognitiveCodeAnalysis\PhpParser\AnnotationVisitor', 'fqcnCache');

        // Clear regex pattern caches
        $this->clearStaticProperty('Phauthentic\CognitiveCodeAnalysis\Business\DirectoryScanner', 'compiledPatterns');
        $this->clearStaticProperty('Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollector', 'compiledPatterns');

        // Clear accumulated data in visitors
        $this->combinedVisitor->resetAllBetweenFiles();
    }

    /**
     * Clear a static property using reflection.
     */
    private function clearStaticProperty(string $className, string $propertyName): void
    {
        try {
            /** @var class-string $className */
            $reflection = new \ReflectionClass($className);
            if ($reflection->hasProperty($propertyName)) {
                $property = $reflection->getProperty($propertyName);
                $property->setAccessible(true);
                $property->setValue(null, []);
            }
        } catch (\ReflectionException $e) {
            // Ignore reflection errors
        }
    }
}
