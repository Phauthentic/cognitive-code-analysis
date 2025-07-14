<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

/**
 *
 */
class HalsteadMetricsVisitor extends NodeVisitorAbstract
{
    private array $operators = [];
    private array $operands = [];
    private ?string $currentClassName = null;
    private ?string $currentNamespace = null;
    private array $classMetrics = [];
    private ?string $currentMethodName = null;
    private array $methodOperators = [];
    private array $methodOperands = [];
    private array $methodMetrics = [];

    /**
     * Processes each node in the abstract syntax tree (AST) as it is entered.
     *
     * This method performs different actions based on the type of node encountered:
     *
     * - **Namespace_ Node**: Updates the current namespace context when a namespace declaration is encountered.
     * - **Class_ Node**: Updates the current class name context when a class declaration is encountered.
     * - **Operator Node**: Adds the operator type to the internal list of operators if the node is recognized as an operator.
     * - **Operand Node**: Adds the operand value to the internal list of operands if the node is recognized as an operand.
     *
     * The node processing is part of gathering metrics for the Halstead complexity measurement. Operators and operands
     * are collected to later calculate metrics such as program length, vocabulary, volume, difficulty, and effort.
     *
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Namespace_) {
            $this->setCurrentNamespace($node);
        } elseif ($node instanceof Class_) {
            $this->setCurrentClassName($node);
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            $this->currentMethodName = $node->name->toString();
            $this->methodOperators = [];
            $this->methodOperands = [];
        } elseif ($this->isOperator($node)) {
            $this->addOperator($node);
            if ($this->currentMethodName !== null) {
                $this->methodOperators[] = $node->getType();
            }
        } elseif ($this->isOperand($node)) {
            $this->addOperand($node);
            if ($this->currentMethodName !== null) {
                $this->methodOperands[] = $this->getOperandValue($node);
            }
        }
    }

    private function setCurrentNamespace(Namespace_ $node): void
    {
        $this->currentNamespace = $node->name instanceof \PhpParser\Node\Name ? $node->name->toString() : '';
    }

    private function setCurrentClassName(Class_ $node): void
    {
        $className = $node->name ? $node->name->toString() : '';
        // Always build FQCN as "namespace\class" (even if namespace is empty)
        $fqcn = ($this->currentNamespace !== '' ? $this->currentNamespace . '\\' : '') . $className;
        $this->currentClassName = $this->normalizeFqcn($fqcn);
    }

    /**
     * Ensures the FQCN always starts with a backslash.
     */
    private function normalizeFqcn(string $fqcn): string
    {
        return str_starts_with($fqcn, '\\') ? $fqcn : '\\' . $fqcn;
    }

    private function addOperator(Node $node): void
    {
        $this->operators[] = $node->getType();
    }

    private function addOperand(Node $node): void
    {
        $this->operands[] = $this->getOperandValue($node);
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            // Store metrics for the method before resetting
            if ($this->currentClassName !== null && $this->currentMethodName !== null) {
                $methodKey = $this->currentClassName . '::' . $this->currentMethodName;
                $this->methodMetrics[$methodKey] = $this->calculateMetricsFor($this->methodOperators, $this->methodOperands, $methodKey);
            }
            $this->currentMethodName = null;
            $this->methodOperators = [];
            $this->methodOperands = [];
        }

        if ($node instanceof Class_) {
            // Store metrics for the class before resetting
            $this->storeClassMetrics();
            $this->resetMetrics();
        }

        if ($node instanceof Namespace_) {
            $this->currentNamespace = '';
        }
    }

    private function isOperator(Node $node): bool
    {
        return $node instanceof Node\Expr\BinaryOp
            || $node instanceof Node\Expr\Assign
            || $node instanceof Node\Expr\FuncCall
            || $node instanceof Node\Expr\MethodCall;
    }

    private function isOperand(Node $node): bool
    {
        return $node instanceof Node\Expr\Variable
            || $node instanceof Node\Scalar\String_
            || $node instanceof Node\Scalar\LNumber
            || $node instanceof Node\Scalar\DNumber;
    }

    private function getOperandValue(Node $node): string
    {
        if ($node instanceof Node\Expr\Variable) {
            return '$' . $node->name;
        }

        if ($node instanceof Node\Scalar\String_) {
            return (string) $node->value;
        }

        if ($node instanceof Node\Scalar\LNumber || $node instanceof Node\Scalar\DNumber) {
            return (string) $node->value;
        }

        return '';
    }

    private function storeClassMetrics(): void
    {
        if ($this->currentClassName !== null) {
            $this->classMetrics[$this->currentClassName] = $this->calculateMetrics();
        }
    }

    public function resetMetrics(): void
    {
        $this->operators = [];
        $this->operands = [];
        $this->currentClassName = null;
    }

    private function calculateMetrics(): array
    {
        // Step 1: Count distinct and total occurrences of operators and operands
        $distinctOperators = $this->countDistinctOperators();
        $distinctOperands = $this->countDistinctOperands();
        $totalOperators = $this->countTotalOperators();
        $totalOperands = $this->countTotalOperands();

        // Step 2: Calculate basic metrics
        $programLength = $this->calculateProgramLength($totalOperators, $totalOperands);
        $programVocabulary = $this->calculateProgramVocabulary($distinctOperators, $distinctOperands);

        // Step 3: Calculate advanced metrics
        $volume = $this->calculateVolume($programLength, $programVocabulary);
        $difficulty = $this->calculateDifficulty($distinctOperators, $totalOperands, $distinctOperands);
        $effort = $difficulty * $volume;

        // Step 4: Prepare the results array
        return [
            'n1' => $distinctOperators,
            'n2' => $distinctOperands,
            'N1' => $totalOperators,
            'N2' => $totalOperands,
            'programLength' => $programLength,
            'programVocabulary' => $programVocabulary,
            'volume' => $volume,
            'difficulty' => $difficulty,
            'effort' => $effort,
            'className' => $this->currentClassName, // Include the FQCN
        ];
    }

    private function calculateMetricsFor(array $operators, array $operands, string $fqName): array
    {
        $distinctOperators = count(array_unique($operators));
        $distinctOperands = count(array_unique($operands));
        $totalOperators = count($operators);
        $totalOperands = count($operands);
        $programLength = $totalOperators + $totalOperands;
        $programVocabulary = $distinctOperators + $distinctOperands;
        $volume = $programVocabulary > 0 ? $programLength * log($programVocabulary, 2) : 0.0;
        $difficulty = ($distinctOperators > 0 && $distinctOperands > 0) ? ($distinctOperators / 2) * ($totalOperands / $distinctOperands) : 0.0;
        $effort = $difficulty * $volume;

        return [
            'n1' => $distinctOperators,
            'n2' => $distinctOperands,
            'N1' => $totalOperators,
            'N2' => $totalOperands,
            'programLength' => $programLength,
            'programVocabulary' => $programVocabulary,
            'volume' => $volume,
            'difficulty' => $difficulty,
            'effort' => $effort,
            'fqName' => $fqName,
        ];
    }

    /**
     * Calculate the program length.
     *
     * @param int $N1 The total occurrences of operators.
     * @param int $N2 The total occurrences of operands.
     * @return int The program length.
     */
    private function calculateProgramLength(int $N1, int $N2): int
    {
        return $N1 + $N2;
    }

    /**
     * Calculate the program vocabulary.
     *
     * @param int $n1 The count of distinct operators.
     * @param int $n2 The count of distinct operands.
     * @return int The program vocabulary.
     */
    private function calculateProgramVocabulary(int $n1, int $n2): int
    {
        return $n1 + $n2;
    }

    /**
     * Calculate the volume of the program.
     *
     * @param int $programLength The length of the program.
     * @param int $programVocabulary The vocabulary of the program.
     * @return float The volume of the program.
     */
    private function calculateVolume(int $programLength, int $programVocabulary): float
    {
        return $programLength * log($programVocabulary, 2);
    }

    /**
     * Calculate the difficulty of the program.
     *
     * @param int $n1 The count of distinct operators.
     * @param int $N2 The total occurrences of operands.
     * @param int $n2 The count of distinct operands.
     * @return float The difficulty of the program.
     */
    private function calculateDifficulty(int $n1, int $N2, int $n2): float
    {
        if ($n2 === 0) {
            return 0.0;
        }
        return ($n1 / 2) * ($N2 / $n2);
    }

    /**
     * Calculate the effort required for the program.
     *
     * @param float $difficulty The difficulty of the program.
     * @param float $volume The volume of the program.
     * @return float The effort required for the program.
     */
    private function calculateEffort(float $difficulty, float $volume): float
    {
        return $difficulty * $volume;
    }

    /**
     * Count the number of distinct operators.
     *
     * @return int The count of distinct operators.
     */
    private function countDistinctOperators(): int
    {
        return count(array_unique($this->operators));
    }

    /**
     * Count the number of distinct operands.
     *
     * @return int The count of distinct operands.
     */
    private function countDistinctOperands(): int
    {
        return count(array_unique($this->operands));
    }

    /**
     * Count the total occurrences of operators.
     *
     * @return int The total occurrences of operators.
     */
    private function countTotalOperators(): int
    {
        return count($this->operators);
    }

    /**
     * Count the total occurrences of operands.
     *
     * @return int The total occurrences of operands.
     */
    private function countTotalOperands(): int
    {
        return count($this->operands);
    }

    public function getMetrics(): array
    {
        // In case we haven't left the last class
        if ($this->currentClassName !== null) {
            $this->storeClassMetrics();
        }

        return [
            'classes' => $this->classMetrics,
            'methods' => $this->methodMetrics,
        ];
    }
}
