<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\PhpParser;

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

            $fqcn = $this->currentNamespace . '\\' . $node->name->toString();
            $this->currentClassName = $this->normalizeFqcn($fqcn);
        }

        return true;
    }

    /**
     * Ensures the FQCN always starts with a backslash.
     */
    private function normalizeFqcn(string $fqcn): string
    {
        return str_starts_with($fqcn, '\\') ? $fqcn : '\\' . $fqcn;
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
        if (is_string($node->name) && !isset($this->methodArguments[$node->name])) {
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
            $this->methodMetrics[$method]['returnCount'] = $this->currentReturnCount;
            $this->methodMetrics[$method]['variableCount'] = count($this->currentVariables);
            $this->methodMetrics[$method]['propertyCallCount'] = $this->propertyCalls;
            $this->methodMetrics[$method]['ifCount'] = $this->ifCount;
            $this->methodMetrics[$method]['ifNestingLevel'] = $this->maxIfNestingLevel;
            $this->methodMetrics[$method]['elseCount'] = $this->elseCount;
            $this->methodMetrics[$method]['lineCount'] = $node->getEndLine() - $node->getStartLine() + 1;
            $this->methodMetrics[$method]['argCount'] = count($node->getParams());
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
