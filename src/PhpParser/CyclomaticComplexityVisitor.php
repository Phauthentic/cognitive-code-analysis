<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\PhpParser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Node visitor to calculate cyclomatic complexity per class and method.
 *
 * Cyclomatic complexity is calculated as:
 * - Base complexity: 1
 * - +1 for each decision point (if, while, for, foreach, switch case, catch, etc.)
 * - +1 for each logical operator (&&, ||, and, or, xor)
 */
class CyclomaticComplexityVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<string, int> Class complexity indexed by class name
     */
    private array $classComplexity = [];

    /**
     * @var array<string, int> Method complexity indexed by "ClassName::methodName"
     */
    private array $methodComplexity = [];

    /**
     * @var array<string, array> Detailed breakdown of complexity factors per method
     */
    private array $methodComplexityBreakdown = [];

    private string $currentNamespace = '';
    private string $currentClassName = '';
    private string $currentMethod = '';

    // Complexity counters for current method
    private int $currentMethodComplexity = 1; // Base complexity
    private int $ifCount = 0;
    private int $elseIfCount = 0;
    private int $elseCount = 0;
    private int $switchCount = 0;
    private int $caseCount = 0;
    private int $defaultCount = 0;
    private int $whileCount = 0;
    private int $doWhileCount = 0;
    private int $forCount = 0;
    private int $foreachCount = 0;
    private int $catchCount = 0;
    private int $logicalAndCount = 0;
    private int $logicalOrCount = 0;
    private int $logicalXorCount = 0;
    private int $ternaryCount = 0;

    public function resetMethodCounters(): void
    {
        $this->currentMethodComplexity = 1; // Base complexity
        $this->ifCount = 0;
        $this->elseIfCount = 0;
        $this->elseCount = 0;
        $this->switchCount = 0;
        $this->caseCount = 0;
        $this->defaultCount = 0;
        $this->whileCount = 0;
        $this->doWhileCount = 0;
        $this->forCount = 0;
        $this->foreachCount = 0;
        $this->catchCount = 0;
        $this->logicalAndCount = 0;
        $this->logicalOrCount = 0;
        $this->logicalXorCount = 0;
        $this->ternaryCount = 0;
    }

    public function enterNode(Node $node): void
    {
        $this->setCurrentNamespaceOnEnterNode($node);
        $this->setCurrentClassOnEnterNode($node);
        $this->handleClassMethodEnter($node);

        if ($this->currentMethod) {
            $this->countDecisionPoints($node);
        }
    }

    public function leaveNode(Node $node): void
    {
        $this->handleClassMethodLeave($node);
        $this->checkNamespaceLeave($node);
        $this->checkClassLeave($node);
    }

    private function setCurrentNamespaceOnEnterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = $node->name instanceof Node\Name ? $node->name->toString() : '';
        }
    }

    private function setCurrentClassOnEnterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_) {
            if ($node->name !== null) {
                $fqcn = $this->currentNamespace . '\\' . $node->name->toString();
                $this->currentClassName = $this->normalizeFqcn($fqcn);
                $this->classComplexity[$this->currentClassName] = 1; // Base complexity for class
            }
        }
    }

    /**
     * Ensures the FQCN always starts with a backslash.
     */
    private function normalizeFqcn(string $fqcn): string
    {
        return str_starts_with($fqcn, '\\') ? $fqcn : '\\' . $fqcn;
    }

    private function handleClassMethodEnter(Node $node): void
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            $this->currentMethod = $node->name->toString();
            $this->resetMethodCounters();
        }
    }

    private function countDecisionPoints(Node $node): void
    {
        match (true) {
            $node instanceof Node\Stmt\If_ => $this->countIfStatement(),
            $node instanceof Node\Stmt\ElseIf_ => $this->countElseIfStatement(),
            $node instanceof Node\Stmt\Else_ => $this->countElseStatement(),
            $node instanceof Node\Stmt\Switch_ => $this->countSwitchStatement(),
            $node instanceof Node\Stmt\Case_ => $this->countCaseStatement(),
            $node instanceof Node\Stmt\While_ => $this->countWhileStatement(),
            $node instanceof Node\Stmt\Do_ => $this->countDoWhileStatement(),
            $node instanceof Node\Stmt\For_ => $this->countForStatement(),
            $node instanceof Node\Stmt\Foreach_ => $this->countForeachStatement(),
            $node instanceof Node\Stmt\Catch_ => $this->countCatchStatement(),
            $node instanceof Node\Expr\BinaryOp\LogicalAnd => $this->countLogicalAnd(),
            $node instanceof Node\Expr\BinaryOp\LogicalOr => $this->countLogicalOr(),
            $node instanceof Node\Expr\BinaryOp\LogicalXor => $this->countLogicalXor(),
            $node instanceof Node\Expr\Ternary => $this->countTernary(),
            default => null,
        };
    }

    private function countIfStatement(): void
    {
        $this->ifCount++;
        $this->currentMethodComplexity++;
    }

    private function countElseIfStatement(): void
    {
        $this->elseIfCount++;
        $this->currentMethodComplexity++;
    }

    private function countElseStatement(): void
    {
        $this->elseCount++;
        // Note: else doesn't add complexity, it's part of the if/elseif chain
    }

    private function countSwitchStatement(): void
    {
        $this->switchCount++;
        $this->currentMethodComplexity++;
    }

    private function countCaseStatement(): void
    {
        $this->caseCount++;
        $this->currentMethodComplexity++;
    }

    private function countWhileStatement(): void
    {
        $this->whileCount++;
        $this->currentMethodComplexity++;
    }

    private function countDoWhileStatement(): void
    {
        $this->doWhileCount++;
        $this->currentMethodComplexity++;
    }

    private function countForStatement(): void
    {
        $this->forCount++;
        $this->currentMethodComplexity++;
    }

    private function countForeachStatement(): void
    {
        $this->foreachCount++;
        $this->currentMethodComplexity++;
    }

    private function countCatchStatement(): void
    {
        $this->catchCount++;
        $this->currentMethodComplexity++;
    }

    private function countLogicalAnd(): void
    {
        $this->logicalAndCount++;
        $this->currentMethodComplexity++;
    }

    private function countLogicalOr(): void
    {
        $this->logicalOrCount++;
        $this->currentMethodComplexity++;
    }

    private function countLogicalXor(): void
    {
        $this->logicalXorCount++;
        $this->currentMethodComplexity++;
    }

    private function countTernary(): void
    {
        $this->ternaryCount++;
        $this->currentMethodComplexity++;
    }

    private function handleClassMethodLeave(Node $node): void
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            $methodKey = "{$this->currentClassName}::{$this->currentMethod}";

            // Store method complexity
            $this->methodComplexity[$methodKey] = $this->currentMethodComplexity;

            // Store detailed breakdown
            $this->methodComplexityBreakdown[$methodKey] = [
                'total' => $this->currentMethodComplexity,
                'base' => 1,
                'if' => $this->ifCount,
                'elseif' => $this->elseIfCount,
                'else' => $this->elseCount,
                'switch' => $this->switchCount,
                'case' => $this->caseCount,
                'default' => $this->defaultCount,
                'while' => $this->whileCount,
                'do_while' => $this->doWhileCount,
                'for' => $this->forCount,
                'foreach' => $this->foreachCount,
                'catch' => $this->catchCount,
                'logical_and' => $this->logicalAndCount,
                'logical_or' => $this->logicalOrCount,
                'logical_xor' => $this->logicalXorCount,
                'ternary' => $this->ternaryCount,
            ];

            // Add method complexity to class complexity
            if (isset($this->classComplexity[$this->currentClassName])) {
                $this->classComplexity[$this->currentClassName] += $this->currentMethodComplexity;
            }

            $this->currentMethod = '';
        }
    }

    private function checkNamespaceLeave(Node $node): void
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = '';
        }
    }

    private function checkClassLeave(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->currentClassName = '';
        }
    }

    /**
     * Get cyclomatic complexity for all classes.
     *
     * @return array<string, int> Class complexity indexed by class name
     */
    public function getClassComplexity(): array
    {
        return $this->classComplexity;
    }

    /**
     * Get cyclomatic complexity for all methods.
     *
     * @return array<string, int> Method complexity indexed by "ClassName::methodName"
     */
    public function getMethodComplexity(): array
    {
        return $this->methodComplexity;
    }

    /**
     * Get detailed breakdown of complexity factors for all methods.
     *
     * @return array<string, array> Detailed breakdown indexed by "ClassName::methodName"
     */
    public function getMethodComplexityBreakdown(): array
    {
        return $this->methodComplexityBreakdown;
    }

    /**
     * Get complexity summary with risk levels.
     *
     * @return array<string, array> Summary with risk assessment
     */
    public function getComplexitySummary(): array
    {
        $summary = [
            'classes' => [],
            'methods' => [],
            'high_risk_methods' => [],
            'very_high_risk_methods' => [],
        ];

        // Class summary
        foreach ($this->classComplexity as $className => $complexity) {
            $summary['classes'][$className] = [
                'complexity' => $complexity,
                'risk_level' => $this->getRiskLevel($complexity),
            ];
        }

        // Method summary
        foreach ($this->methodComplexity as $methodKey => $complexity) {
            $riskLevel = $this->getRiskLevel($complexity);
            $summary['methods'][$methodKey] = [
                'complexity' => $complexity,
                'risk_level' => $riskLevel,
                'breakdown' => $this->methodComplexityBreakdown[$methodKey] ?? [],
            ];

            if ($complexity >= 10) {
                $summary['high_risk_methods'][$methodKey] = $complexity;
            }
            if ($complexity >= 15) {
                $summary['very_high_risk_methods'][$methodKey] = $complexity;
            }
        }

        return $summary;
    }

    /**
     * Determine risk level based on complexity.
     *
     * @param int $complexity The cyclomatic complexity value
     * @return string Risk level: 'low', 'medium', 'high', 'very_high'
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
