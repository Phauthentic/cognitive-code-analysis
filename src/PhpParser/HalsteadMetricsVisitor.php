<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\PhpParser;

use Phauthentic\CognitiveCodeAnalysis\Business\Halstead\HalsteadMetricsCalculatorInterface;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ShortVariable)
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
     * @var array<string, string> Cache for normalized FQCNs
     */
    private static array $fqcnCache = [];

    /**
     * @var AnnotationVisitor|null The annotation visitor to check for ignored items
     */
    private ?AnnotationVisitor $annotationVisitor = null;

    /**
     * @var HalsteadMetricsCalculatorInterface The calculator for Halstead metrics
     */
    private HalsteadMetricsCalculatorInterface $calculator;

    /**
     * Constructor for HalsteadMetricsVisitor.
     *
     * @param HalsteadMetricsCalculatorInterface $calculator The calculator for Halstead metrics
     */
    public function __construct(HalsteadMetricsCalculatorInterface $calculator)
    {
        $this->calculator = $calculator;
    }

    /**
     * Set the annotation visitor to check for ignored items.
     */
    public function setAnnotationVisitor(AnnotationVisitor $annotationVisitor): void
    {
        $this->annotationVisitor = $annotationVisitor;
    }

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
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    public function enterNode(Node $node)
    {
        $result = match (true) {
            $node instanceof Namespace_ => function () use ($node) {
                $this->setCurrentNamespace($node);
            },
            $node instanceof Class_ || $node instanceof Trait_ => function () use ($node) {
                $this->setCurrentClassName($node);

                // Check if this class should be ignored
                if (
                    $this->currentClassName !== null
                    && $this->annotationVisitor !== null
                    && $this->annotationVisitor->isClassIgnored($this->currentClassName)
                ) {
                    $this->currentClassName = null; // Clear the class name if ignored
                }
            },
            $node instanceof Node\Stmt\ClassMethod => function () use ($node) {
                // Skip methods that don't have a class or trait context (interfaces, global functions)
                if (empty($this->currentClassName)) {
                    return;
                }

                $methodKey = $this->currentClassName . '::' . $node->name->toString();

                // Check if this method should be ignored
                if ($this->annotationVisitor !== null && $this->annotationVisitor->isMethodIgnored($methodKey)) {
                    return;
                }

                $this->currentMethodName = $node->name->toString();
                $this->methodOperators = [];
                $this->methodOperands = [];
            },
            $this->isOperator($node) => function () use ($node) {
                $this->addOperator($node);
                if ($this->currentMethodName !== null) {
                    $this->methodOperators[] = $node->getType();
                }
            },
            $this->isOperand($node) => function () use ($node) {
                $this->addOperand($node);
                if ($this->currentMethodName !== null) {
                    $this->methodOperands[] = $this->getOperandValue($node);
                }
            },
            default => null,
        };

        if ($result !== null) {
            $result();
        }
    }

    private function setCurrentNamespace(Namespace_ $node): void
    {
        $this->currentNamespace = $node->name instanceof \PhpParser\Node\Name ? $node->name->toString() : '';
    }

    private function setCurrentClassName(Node $node): void
    {
        // Skip anonymous classes - they don't have a proper class name
        if ($node->name === null) {
            $this->currentClassName = null;
            return;
        }

        $className = $node->name->toString();
        // Always build FQCN as "namespace\class" (even if namespace is empty)
        $fqcn = ($this->currentNamespace !== '' ? $this->currentNamespace . '\\' : '') . $className;
        $this->currentClassName = $this->normalizeFqcn($fqcn);
    }

    /**
     * Ensures the FQCN always starts with a backslash.
     */
    private function normalizeFqcn(string $fqcn): string
    {
        if (!isset(self::$fqcnCache[$fqcn])) {
            self::$fqcnCache[$fqcn] = str_starts_with($fqcn, '\\') ? $fqcn : '\\' . $fqcn;
        }

        return self::$fqcnCache[$fqcn];
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
                $this->methodMetrics[$methodKey] = $this->calculator->calculateMetrics($this->methodOperators, $this->methodOperands, $methodKey);
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
            $this->classMetrics[$this->currentClassName] = $this->calculator->calculateMetrics($this->operators, $this->operands, $this->currentClassName);
        }
    }

    public function resetMetrics(): void
    {
        // Clear current state, but keep accumulated metrics for retrieval
        $this->operators = [];
        $this->operands = [];
        $this->currentClassName = null;
        $this->currentNamespace = null;
        $this->currentMethodName = null;
        $this->methodOperators = [];
        $this->methodOperands = [];
    }

    /**
     * Reset all accumulated data (for memory cleanup between files).
     */
    public function resetAll(): void
    {
        // Clear all accumulated data to prevent memory leaks
        $this->operators = [];
        $this->operands = [];
        $this->currentClassName = null;
        $this->currentNamespace = null;
        $this->classMetrics = [];
        $this->currentMethodName = null;
        $this->methodOperators = [];
        $this->methodOperands = [];
        $this->methodMetrics = [];
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
