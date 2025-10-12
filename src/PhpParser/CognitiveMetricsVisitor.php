<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\PhpParser;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

/**
 * Node visitor to collect method metrics.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CognitiveMetricsVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<string, array>
     */
    private array $methodMetrics = [];
    private string $currentNamespace = '';
    private string $currentClassName = '';
    private string $currentMethod = '';
    private int $currentReturnCount = 0;

    /**
     * @var array<string, string> Cache for normalized FQCNs
     */
    private static array $fqcnCache = [];

    /**
     * @var AnnotationVisitor|null The annotation visitor to check for ignored items
     */
    private ?AnnotationVisitor $annotationVisitor = null;



    /**
     * @var array<string, bool>
     */
    private array $methodArguments = []; // Tracks method arguments

    /**
     * @var array<string, bool>
     */
    private array $currentVariables = []; // Tracks variables declared in the method body

    /**
     * @var array<string, bool>
     */
    private array $accessedProperties = []; // Tracks properties accessed in the current method
    private int $propertyCalls = 0;
    private int $currentIfNestingLevel = 0;
    private int $maxIfNestingLevel = 0;
    private int $elseCount = 0;
    private int $ifCount = 0;

    /**
     * Set the annotation visitor to check for ignored items.
     */
    public function setAnnotationVisitor(AnnotationVisitor $annotationVisitor): void
    {
        $this->annotationVisitor = $annotationVisitor;
    }

    public function resetValues(): void
    {
        $this->currentReturnCount = 0;
        $this->methodArguments = [];
        $this->currentVariables = [];
        $this->accessedProperties = [];
        $this->propertyCalls = 0;
        $this->currentIfNestingLevel = 0;
        $this->maxIfNestingLevel = 0;
        $this->elseCount = 0;
        $this->ifCount = 0;
    }

    /**
     * Reset all data including method metrics (for memory cleanup between files).
     */
    public function resetAll(): void
    {
        // Clear all accumulated data to prevent memory leaks
        $this->methodMetrics = [];
        $this->currentNamespace = '';
        $this->currentClassName = '';
        $this->currentMethod = '';
        $this->resetValues();
    }

    /**
     * Create the initial metrics array for a method.
     */
    private function createMetricsArray(Node\Stmt\ClassMethod $node): array
    {
        return [
            'line' => $node->getStartLine(),
            'lineCount' => $this->calculateLineCount($node),
            'argCount' => $this->countMethodArguments($node),
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0,
            'ifCount' => 0,
        ];
    }

    /**
     * Check if we have a valid class context for method processing.
     */
    private function isValidContext(): bool
    {
        return !empty($this->currentClassName);
    }

    /**
     * Build the method key for the current class and method.
     */
    private function buildMethodKey(): string
    {
        return "{$this->currentClassName}::{$this->currentMethod}";
    }

    private function classMethodOnEnterNode(Node $node): void
    {
        // Skip methods that don't have a class or trait context (interfaces, global functions)
        if (!$this->isClassMethodNode($node) || !$this->isValidContext()) {
            return;
        }

        // Check if this method should be ignored
        if ($this->annotationVisitor !== null) {
            $methodKey = $this->currentClassName . '::' . $node->name->toString();
            if ($this->annotationVisitor->isMethodIgnored($methodKey)) {
                return;
            }
        }

        $this->initializeMethodContext($node);
        $this->resetValues();
        $this->trackMethodArguments($node);
        // Note: recordMethodMetrics is now called in leaveNode to ensure currentMethod is set
    }

    /**
     * Check if the node is an instance of Node\Stmt\ClassMethod.
     *
     * @param Node $node The node to check.
     * @return bool True if the node is a class method, false otherwise.
     */
    private function isClassMethodNode(Node $node): bool
    {
        return $node instanceof Node\Stmt\ClassMethod;
    }

    /**
     * Initialize the context for the current method.
     *
     * @param Node\Stmt\ClassMethod $node The class method node.
     * @return void
     */
    private function initializeMethodContext(Node\Stmt\ClassMethod $node): void
    {
        $this->currentMethod = $node->name->toString();
    }

    /**
     * Track the method arguments in the current context.
     *
     * @param Node\Stmt\ClassMethod $node The class method node.
     * @return void
     */
    private function trackMethodArguments(Node\Stmt\ClassMethod $node): void
    {
        foreach ($node->params as $param) {
            if (!$this->isVariable($param->var)) {
                continue;
            }

            $this->methodArguments[$param->var->name] = true;
        }
    }

    /**
     * Check if the node is a variable.
     *
     * @param Node $node The node to check.
     * @return bool True if the node is a variable, false otherwise.
     */
    private function isVariable($node): bool
    {
        return $node instanceof Node\Expr\Variable;
    }

    /**
     * Calculate the line count for the given method node.
     *
     * @param Node\Stmt\ClassMethod $node The class method node.
     * @return int The number of lines in the method.
     */
    private function calculateLineCount(Node\Stmt\ClassMethod $node): int
    {
        return $node->getEndLine() - $node->getStartLine() + 1;
    }

    /**
     * Count the number of arguments in the given method node.
     *
     * @param Node\Stmt\ClassMethod $node The class method node.
     * @return int The number of arguments in the method.
     */
    private function countMethodArguments(Node\Stmt\ClassMethod $node): int
    {
        return count($node->params);
    }

    private function setCurrentNamespaceOnEnterNode(Node $node): void
    {
        if (!($node instanceof Node\Stmt\Namespace_)) {
            return;
        }

        $this->currentNamespace = $node->name instanceof Node\Name ? $node->name->toString() : '';
    }

    /**
     * Check if the node is a class or trait declaration.
     */
    private function isClassOrTraitNode(Node $node): bool
    {
        return $node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Trait_;
    }

    private function setCurrentClassOnEnterNode(Node $node): bool
    {
        if (!$this->isClassOrTraitNode($node)) {
            return true;
        }

        if ($node->name === null) {
            // Skip anonymous classes - they don't have a proper class name
            return false;
        }

        $fqcn = $this->currentNamespace . '\\' . $node->name->toString();
        $this->currentClassName = $this->normalizeFqcn($fqcn);

        // Check if this class should be ignored
        if ($this->annotationVisitor !== null && $this->annotationVisitor->isClassIgnored($this->currentClassName)) {
            $this->currentClassName = ''; // Clear the class name if ignored
            return false;
        }

        return true;
    }

    /**
     * Ensures the FQCN always starts with a backslash.
     * Uses caching to avoid repeated string operations.
     */
    private function normalizeFqcn(string $fqcn): string
    {
        if (!isset(self::$fqcnCache[$fqcn])) {
            self::$fqcnCache[$fqcn] = str_starts_with($fqcn, '\\') ? $fqcn : '\\' . $fqcn;
        }

        return self::$fqcnCache[$fqcn];
    }

    public function enterNode(Node $node): int|Node|null
    {
        $this->setCurrentNamespaceOnEnterNode($node);
        if (!$this->setCurrentClassOnEnterNode($node)) {
            // Skip the entire subtree for anonymous classes or ignored classes
            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        $this->classMethodOnEnterNode($node);

        if ($this->currentMethod) {
            $this->gatherMetrics($node);
        }

        return null;
    }

    private function gatherMetrics(Node $node): void
    {
        match (true) {
            $node instanceof Node\Stmt\Return_ => $this->incrementReturnCount(),
            $node instanceof Node\Expr\Variable => $this->countVariablesNotAlreadyTrackedAsArguments($node),
            $node instanceof Node\Expr\PropertyFetch => $this->trackPropertyFetch($node),
            $node instanceof Node\Stmt\If_ => $this->trackIfStatement(),
            $node instanceof Node\Stmt\Else_,
            $node instanceof Node\Stmt\ElseIf_ => $this->incrementElseCount(),
            default => null, // Do nothing for other node types
        };
    }

    private function incrementReturnCount(): void
    {
        $this->currentReturnCount++;
    }

    /**
     * Count variables that are not already tracked as method arguments.
     *
     * Important note about variable handling in this method:
     *
     * In PhpParser, the $node->name property of a Node\Expr\Variable can be either:
     *
     * - a string (for simple variables like $foo)
     * - or an Expr node (usually another Variable or an expression, for complex variables like ${$bar} or variable
     *   variables)
     *
     * This is because PHP allows variable variables and complex expressions as variable names, so the parser represents
     * them as objects rather than plain strings. Always check the type before using it as an array key.
     *
     * When $node->name is an object (not a string), it represents complex or variable variables (like ${$foo})
     * in PHP. These cases are rare in typical code and are challenging to statically analyze. For metrics like
     * variable counting, they are usually not relevant or are intentionally skipped, since you cannot reliably
     * determine the variable name at parse time.
     *
     * @link https://github.com/Phauthentic/cognitive-code-analysis/issues
     */
    private function countVariablesNotAlreadyTrackedAsArguments(Node\Expr\Variable $node): void
    {
        if (!is_string($node->name) || isset($this->methodArguments[$node->name])) {
            return;
        }

        $this->currentVariables[$node->name] = true;
    }

    private function trackPropertyFetch(Node\Expr\PropertyFetch $node): void
    {
        // Skip if property name is a variable or doesn't have toString method
        if (!$node->name instanceof Node\Identifier) {
            return;
        }

        $property = $node->name->toString();

        // Only track new properties to avoid duplicates
        if (isset($this->accessedProperties[$property])) {
            return;
        }

        $this->accessedProperties[$property] = true;
        $this->propertyCalls++;
    }

    private function trackIfStatement(): void
    {
        $this->ifCount++;
        $this->currentIfNestingLevel++;

        if ($this->currentIfNestingLevel <= $this->maxIfNestingLevel) {
            return;
        }

        $this->maxIfNestingLevel = $this->currentIfNestingLevel;
    }

    private function incrementElseCount(): void
    {
        $this->elseCount++;
    }

    private function checkNestingLevelOnLeaveNode(Node $node): void
    {
        if (!($node instanceof Node\Stmt\If_)) {
            return;
        }

        $this->currentIfNestingLevel--;
    }



    private function writeMetricsOnLeaveNode(Node $node): void
    {
        if (!$node instanceof Node\Stmt\ClassMethod) {
            return;
        }

        // Skip methods that don't have a class or trait context (interfaces, global functions)
        if (!$this->isValidContext()) {
            $this->currentMethod = '';
            return;
        }

        // Check if this method should be ignored
        if ($this->annotationVisitor !== null) {
            $methodKey = $this->currentClassName . '::' . $node->name->toString();
            if ($this->annotationVisitor->isMethodIgnored($methodKey)) {
                $this->currentMethod = '';
                return;
            }
        }

        // Record the method metrics if they haven't been recorded yet
        $methodKey = $this->buildMethodKey();
        if (!isset($this->methodMetrics[$methodKey])) {
            $this->methodMetrics[$methodKey] = $this->createMetricsArray($node);
        }

        // Update the metrics with the collected values
        $this->methodMetrics[$methodKey]['returnCount'] = $this->currentReturnCount;
        $this->methodMetrics[$methodKey]['variableCount'] = count($this->currentVariables);
        $this->methodMetrics[$methodKey]['propertyCallCount'] = $this->propertyCalls;
        $this->methodMetrics[$methodKey]['ifCount'] = $this->ifCount;
        $this->methodMetrics[$methodKey]['ifNestingLevel'] = $this->maxIfNestingLevel;
        $this->methodMetrics[$methodKey]['elseCount'] = $this->elseCount;
        $this->methodMetrics[$methodKey]['lineCount'] = $node->getEndLine() - $node->getStartLine() + 1;
        $this->methodMetrics[$methodKey]['argCount'] = count($node->getParams());
        $this->currentMethod = '';
    }

    private function checkNameSpaceOnLeaveNode(Node $node): void
    {
        if (!($node instanceof Node\Stmt\Namespace_)) {
            return;
        }

        $this->currentNamespace = '';
    }

    private function checkClassOnLeaveNode(Node $node): void
    {
        if (!$this->isClassOrTraitNode($node)) {
            return;
        }

        if (!empty($this->currentMethod)) {
            // Don't clear the class context if we're still processing a method
            return;
        }

        $this->currentClassName = '';
    }

    public function leaveNode(Node $node): void
    {
        $this->checkNestingLevelOnLeaveNode($node);
        $this->writeMetricsOnLeaveNode($node);
        $this->checkNameSpaceOnLeaveNode($node);
        $this->checkClassOnLeaveNode($node);
    }

    public function getMethodMetrics(): array
    {
        // Filter out any incomplete metrics that might have slipped through
        $completeMetrics = [];
        foreach ($this->methodMetrics as $methodKey => $metrics) {
            // Ensure the method key contains a class name and method name (not just ::method or ClassName::)
            if (strpos($methodKey, '::') <= 0 || str_starts_with($methodKey, '::') || str_ends_with($methodKey, '::')) {
                continue;
            }

            $completeMetrics[$methodKey] = $metrics;
        }

        return $completeMetrics;
    }
}
