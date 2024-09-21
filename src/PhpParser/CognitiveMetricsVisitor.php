<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\PhpParser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Node visitor to collect method metrics.
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

    private function classMethodOnEnterNode(Node $node): void
    {
        if (!$this->isClassMethodNode($node)) {
            return;
        }

        $this->initializeMethodContext($node);
        $this->resetValues();
        $this->recordMethodMetrics($node);
        $this->trackMethodArguments($node);
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
     * Record the initial metrics for the current method.
     *
     * @param Node\Stmt\ClassMethod $node The class method node.
     * @return void
     */
    private function recordMethodMetrics(Node\Stmt\ClassMethod $node): void
    {
        $this->methodMetrics["{$this->currentClassName}::{$this->currentMethod}"] = [
            'line_count' => $this->calculateLineCount($node),
            'arg_count' => $this->countMethodArguments($node),
            'return_count' => 0,
            'variable_count' => 0,
            'property_call_count' => 0,
            'if_nesting_level' => 0,
            'else_count' => 0,
            'if_count' => 0,
        ];
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
            if ($this->isVariable($param->var)) {
                $this->methodArguments[$param->var->name] = true;
            }
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
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = $node->name instanceof Node\Name ? $node->name->toString() : '';
        }
    }

    private function setCurrentClassOnEnterNode(Node $node): bool
    {
        if ($node instanceof Node\Stmt\Class_) {
            if ($node->name === null) {
                return false;
            }

            $this->currentClassName = $this->currentNamespace . '\\' . $node->name->toString();
        }

        return true;
    }

    public function enterNode(Node $node): void
    {
        $this->setCurrentNamespaceOnEnterNode($node);
        if (!$this->setCurrentClassOnEnterNode($node)) {
            return;
        }

        $this->classMethodOnEnterNode($node);

        if ($this->currentMethod) {
            $this->gatherMetrics($node);
        }
    }

    private function gatherMetrics(Node $node): void
    {
        match (true) {
            $node instanceof Node\Stmt\Return_ => $this->incrementReturnCount(),
            $node instanceof Node\Expr\Variable => $this->trackVariable($node),
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

    private function trackVariable(Node\Expr\Variable $node): void
    {
        // Only count variables not already tracked as arguments
        if (!isset($this->methodArguments[$node->name])) {
            $this->currentVariables[$node->name] = true;
        }
    }

    private function trackPropertyFetch(Node\Expr\PropertyFetch $node): void
    {
        if (
            $node->name instanceof Node\Expr\Variable
            || !method_exists($node->name, 'toString')
        ) {
            return;
        }

        $property = $node->name->toString();
        if (!isset($this->accessedProperties[$property])) {
            $this->accessedProperties[$property] = true;
            $this->propertyCalls++;
        }
    }

    private function trackIfStatement(): void
    {
        $this->ifCount++;
        $this->currentIfNestingLevel++;

        if ($this->currentIfNestingLevel > $this->maxIfNestingLevel) {
            $this->maxIfNestingLevel = $this->currentIfNestingLevel;
        }
    }

    private function incrementElseCount(): void
    {
        $this->elseCount++;
    }

    private function checkNestingLevelOnLeaveNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\If_) {
            $this->currentIfNestingLevel--;
        }
    }

    private function writeMetricsOnLeaveNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            $method = "{$this->currentClassName}::{$this->currentMethod}";
            $this->methodMetrics[$method]['return_count'] = $this->currentReturnCount;
            $this->methodMetrics[$method]['variable_count'] = count($this->currentVariables);
            $this->methodMetrics[$method]['property_call_count'] = $this->propertyCalls;
            $this->methodMetrics[$method]['if_count'] = $this->ifCount;
            $this->methodMetrics[$method]['if_nesting_level'] = $this->maxIfNestingLevel;
            $this->methodMetrics[$method]['else_count'] = $this->elseCount;
            $this->methodMetrics[$method]['line_count'] = $node->getEndLine() - $node->getStartLine() + 1;
            $this->methodMetrics[$method]['arg_count'] = count($node->getParams());
            $this->currentMethod = '';
        }
    }

    private function checkNameSpaceOnLeaveNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = '';
        }
    }

    private function checkClassOnLeaveNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->currentClassName = '';
        }
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
        return $this->methodMetrics;
    }
}
