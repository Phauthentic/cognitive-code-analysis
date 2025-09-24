<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\PhpParser;

use PhpParser\Node;
use PhpParser\NodeVisitor;

/**
 * Combined visitor that processes all metrics in a single AST traversal.
 *
 * This eliminates the need for multiple AST parsing passes, significantly improving performance.
 */
class CombinedMetricsVisitor implements NodeVisitor
{
    private AnnotationVisitor $annotationVisitor;
    private CognitiveMetricsVisitor $cognitiveVisitor;
    private CyclomaticComplexityVisitor $cyclomaticVisitor;
    private HalsteadMetricsVisitor $halsteadVisitor;

    public function __construct()
    {
        $this->annotationVisitor = new AnnotationVisitor();
        $this->cognitiveVisitor = new CognitiveMetricsVisitor();
        $this->cyclomaticVisitor = new CyclomaticComplexityVisitor();
        $this->halsteadVisitor = new HalsteadMetricsVisitor();
    }

    public function beforeTraverse(array $nodes): ?array
    {
        // Reset all visitors before traversal
        $this->resetAll();

        return null;
    }

    public function enterNode(Node $node): int|Node|null
    {
        // Process all visitors in sequence
        $result1 = $this->annotationVisitor->enterNode($node);
        $result2 = $this->cognitiveVisitor->enterNode($node);
        $result3 = $this->cyclomaticVisitor->enterNode($node);
        $result4 = $this->halsteadVisitor->enterNode($node);

        // If any visitor wants to skip children, respect that
        if (
            $result1 === NodeVisitor::DONT_TRAVERSE_CHILDREN ||
            $result2 === NodeVisitor::DONT_TRAVERSE_CHILDREN ||
            $result3 === NodeVisitor::DONT_TRAVERSE_CHILDREN ||
            $result4 === NodeVisitor::DONT_TRAVERSE_CHILDREN
        ) {
            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    public function leaveNode(Node $node): int|Node|null
    {
        // Process all visitors in sequence
        $this->annotationVisitor->leaveNode($node);
        $this->cognitiveVisitor->leaveNode($node);
        $this->cyclomaticVisitor->leaveNode($node);
        $this->halsteadVisitor->leaveNode($node);

        return null;
    }

    public function afterTraverse(array $nodes): ?array
    {
        return null;
    }

    /**
     * Reset all visitors to their initial state.
     */
    public function resetAll(): void
    {
        $this->annotationVisitor->reset();
        $this->cognitiveVisitor->resetValues();
        $this->cyclomaticVisitor->resetAll();
        $this->halsteadVisitor->resetMetrics();
    }

    /**
     * Reset all visitors between files (for memory cleanup).
     */
    public function resetAllBetweenFiles(): void
    {
        $this->annotationVisitor->resetContext();
        $this->cognitiveVisitor->resetAll();
        $this->cyclomaticVisitor->resetAll();
        $this->halsteadVisitor->resetAll();
    }

    /**
     * Get method metrics from the cognitive visitor.
     */
    public function getMethodMetrics(): array
    {
        return $this->cognitiveVisitor->getMethodMetrics();
    }

    /**
     * Get method complexity from the cyclomatic visitor.
     */
    public function getMethodComplexity(): array
    {
        return $this->cyclomaticVisitor->getMethodComplexity();
    }

    /**
     * Get method metrics from the Halstead visitor.
     */
    public function getHalsteadMethodMetrics(): array
    {
        return $this->halsteadVisitor->getMethodMetrics();
    }

    /**
     * Get ignored items from the annotation visitor.
     */
    public function getIgnored(): array
    {
        return $this->annotationVisitor->getIgnored();
    }

    /**
     * Set the annotation visitor for the cognitive visitor.
     */
    public function setAnnotationVisitor(): void
    {
        $this->cognitiveVisitor->setAnnotationVisitor($this->annotationVisitor);
    }
}
