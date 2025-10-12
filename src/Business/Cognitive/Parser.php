<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Halstead\HalsteadMetricsCalculator;
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
use ReflectionClass;

class Parser
{
    protected PhpParser $parser;
    protected AnnotationVisitor $annotationVisitor;
    protected CognitiveMetricsVisitor $cognitiveMetricsVisitor;
    protected CyclomaticComplexityVisitor $cyclomaticComplexityVisitor;
    protected HalsteadMetricsVisitor $halsteadMetricsVisitor;
    protected CombinedMetricsVisitor $combinedVisitor;
    protected HalsteadMetricsCalculator $halsteadCalculator;

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

        $this->halsteadCalculator = new HalsteadMetricsCalculator();
        $this->halsteadMetricsVisitor = new HalsteadMetricsVisitor($this->halsteadCalculator);
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

        // Get all metrics before resetting
        $methodMetrics = $this->combinedVisitor->getMethodMetrics();
        $cyclomaticMetrics = $this->combinedVisitor->getMethodComplexity();
        $halsteadMetrics = $this->combinedVisitor->getHalsteadMethodMetrics();

        // Now reset the combined visitor
        $this->combinedVisitor->resetAll();

        // Add cyclomatic complexity to method metrics
        foreach ($cyclomaticMetrics as $method => $complexityData) {
            if (!isset($methodMetrics[$method])) {
                continue;
            }

            $complexity = $complexityData['complexity'] ?? $complexityData;
            $riskLevel = $complexityData['risk_level'] ?? $this->getRiskLevel($complexity);
            $methodMetrics[$method]['cyclomatic_complexity'] = [
                'complexity' => $complexity,
                'risk_level' => $riskLevel
            ];
        }

        // Add Halstead metrics to method metrics
        foreach ($halsteadMetrics as $method => $metrics) {
            if (!isset($methodMetrics[$method])) {
                continue;
            }

            $methodMetrics[$method]['halstead'] = $metrics;
        }

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
            $reflection = new ReflectionClass($className);
            if ($reflection->hasProperty($propertyName)) {
                $property = $reflection->getProperty($propertyName);
                $property->setAccessible(true);
                $property->setValue(null, []);
            }
        } catch (\ReflectionException $e) {
            // Ignore reflection errors
        }
    }

    /**
     * Calculate risk level based on cyclomatic complexity.
     */
    private function getRiskLevel(int $complexity): string
    {
        return match (true) {
            $complexity <= 5 => 'low',
            $complexity <= 10 => 'medium',
            $complexity <= 15 => 'high',
            default => 'very_high',
        };
    }
}
