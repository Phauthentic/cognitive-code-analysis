<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\PhpParser;

use Phauthentic\CognitiveCodeAnalysis\Business\Understandability\UnderstandabilityCalculatorInterface;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitorAbstract;

/**
 * Sonar Cognitive Complexity (understandability) per method.
 *
 * @see https://www.sonarsource.com/resources/cognitive-complexity/
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class UnderstandabilityVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<string, int>
     */
    private array $methodComplexity = [];

    /**
     * @var array<string, array<string, int>>
     */
    private array $methodBreakdown = [];

    private string $currentNamespace = '';
    private string $currentClassName = '';
    private string $currentMethodName = '';
    private string $currentMethodKey = '';

    private int $nestingLevel = 0;
    private int $score = 0;
    private int $structuralCount = 0;
    private int $hybridCount = 0;
    private int $fundamentalCount = 0;
    private int $nestingIncrementCount = 0;
    private int $recursionCount = 0;
    private bool $hasRecursion = false;

    /**
     * @var array<string, string>
     */
    private static array $fqcnCache = [];

    private ?AnnotationVisitor $annotationVisitor = null;

    public function __construct(
        private readonly UnderstandabilityCalculatorInterface $calculator,
    ) {
    }

    public function setAnnotationVisitor(AnnotationVisitor $annotationVisitor): void
    {
        $this->annotationVisitor = $annotationVisitor;
    }

    public function resetMethodCounters(): void
    {
        $this->nestingLevel = 0;
        $this->score = 0;
        $this->structuralCount = 0;
        $this->hybridCount = 0;
        $this->fundamentalCount = 0;
        $this->nestingIncrementCount = 0;
        $this->recursionCount = 0;
        $this->hasRecursion = false;
    }

    public function resetAll(): void
    {
        $this->methodComplexity = [];
        $this->methodBreakdown = [];
        $this->currentNamespace = '';
        $this->currentClassName = '';
        $this->currentMethodName = '';
        $this->currentMethodKey = '';
        $this->resetMethodCounters();
    }

    public function enterNode(Node $node): void
    {
        $this->setCurrentNamespaceOnEnterNode($node);
        $this->setCurrentClassOnEnterNode($node);
        $this->handleClassMethodEnter($node);

        if ($this->currentMethodKey === '') {
            return;
        }

        $this->countRecursion($node);
        $this->countLogicalOperator($node);
        $this->countControlFlowIncrements($node);
    }

    public function leaveNode(Node $node): void
    {
        $this->leaveControlFlowNesting($node);
        $this->handleClassMethodLeave($node);
        $this->checkNamespaceLeave($node);
        $this->checkClassLeave($node);
    }

    /**
     * @return array<string, int>
     */
    public function getMethodComplexity(): array
    {
        return $this->methodComplexity;
    }

    /**
     * @return array<string, mixed>
     */
    public function getComplexitySummary(): array
    {
        return $this->calculator->createSummary($this->methodComplexity, $this->methodBreakdown);
    }

    private function setCurrentNamespaceOnEnterNode(Node $node): void
    {
        if (!$node instanceof Stmt\Namespace_) {
            return;
        }

        $this->currentNamespace = $node->name instanceof Node\Name ? $node->name->toString() : '';
    }

    private function setCurrentClassOnEnterNode(Node $node): void
    {
        if (!$node instanceof Stmt\Class_ && !$node instanceof Stmt\Trait_) {
            return;
        }

        if ($node->name === null) {
            return;
        }

        $fqcn = $this->currentNamespace . '\\' . $node->name->toString();
        $this->currentClassName = $this->normalizeFqcn($fqcn);

        if ($this->annotationVisitor !== null && $this->annotationVisitor->isClassIgnored($this->currentClassName)) {
            $this->currentClassName = '';
        }
    }

    private function normalizeFqcn(string $fqcn): string
    {
        if (!isset(self::$fqcnCache[$fqcn])) {
            self::$fqcnCache[$fqcn] = str_starts_with($fqcn, '\\') ? $fqcn : '\\' . $fqcn;
        }

        return self::$fqcnCache[$fqcn];
    }

    private function handleClassMethodEnter(Node $node): void
    {
        if (!$node instanceof Stmt\ClassMethod) {
            return;
        }

        if ($this->currentClassName === '') {
            return;
        }

        $methodKey = $this->currentClassName . '::' . $node->name->toString();

        if ($this->annotationVisitor !== null && $this->annotationVisitor->isMethodIgnored($methodKey)) {
            return;
        }

        $this->currentMethodName = $node->name->toString();
        $this->currentMethodKey = $methodKey;
        $this->resetMethodCounters();
    }

    private function handleClassMethodLeave(Node $node): void
    {
        if (!$node instanceof Stmt\ClassMethod) {
            return;
        }

        if ($this->currentMethodKey === '') {
            return;
        }

        if ($this->hasRecursion) {
            $this->addFundamental();
            $this->recursionCount++;
        }

        $incrementCounts = [
            'total' => $this->score,
            'structural' => $this->structuralCount,
            'hybrid' => $this->hybridCount,
            'fundamental' => $this->fundamentalCount,
            'nesting' => $this->nestingIncrementCount,
            'recursion' => $this->recursionCount,
        ];

        $this->methodComplexity[$this->currentMethodKey] = $this->score;
        $this->methodBreakdown[$this->currentMethodKey] = $this->calculator->createBreakdown(
            $incrementCounts,
            $this->score,
        );

        $this->currentMethodName = '';
        $this->currentMethodKey = '';
        $this->resetMethodCounters();
    }

    private function countControlFlowIncrements(Node $node): void
    {
        match (true) {
            $node instanceof Stmt\If_ => $this->addStructuralAndIncreaseNesting(),
            $node instanceof Stmt\ElseIf_ => $this->addHybridAndIncreaseNesting(),
            $node instanceof Stmt\Else_ => $this->addHybridAndIncreaseNesting(),
            $node instanceof Expr\Ternary => $this->addStructuralAndIncreaseNesting(),
            $node instanceof Stmt\Switch_ => $this->enterSwitch(),
            $node instanceof Expr\Match_ => $this->enterMatch(),
            $node instanceof Stmt\For_,
            $node instanceof Stmt\Foreach_,
            $node instanceof Stmt\While_,
            $node instanceof Stmt\Do_ => $this->addStructuralAndIncreaseNesting(),
            $node instanceof Stmt\Catch_ => $this->addStructuralAndIncreaseNesting(),
            $node instanceof Expr\Closure,
            $node instanceof Expr\ArrowFunction => $this->enterClosure(),
            $node instanceof Stmt\Goto_ => $this->addFundamental(),
            $node instanceof Stmt\Break_,
            $node instanceof Stmt\Continue_ => $this->countJumpStatement($node),
            default => null,
        };
    }

    private function leaveControlFlowNesting(Node $node): void
    {
        match (true) {
            $node instanceof Stmt\If_,
            $node instanceof Stmt\ElseIf_,
            $node instanceof Stmt\Else_,
            $node instanceof Expr\Ternary,
            $node instanceof Stmt\For_,
            $node instanceof Stmt\Foreach_,
            $node instanceof Stmt\While_,
            $node instanceof Stmt\Do_,
            $node instanceof Stmt\Catch_ => $this->decreaseNesting(),
            $node instanceof Stmt\Switch_ => $this->leaveSwitch(),
            $node instanceof Expr\Match_ => $this->leaveMatch(),
            $node instanceof Expr\Closure,
            $node instanceof Expr\ArrowFunction => $this->leaveClosure(),
            default => null,
        };
    }

    private function enterSwitch(): void
    {
        $this->addStructuralAndIncreaseNesting();
    }

    private function leaveSwitch(): void
    {
        $this->decreaseNesting();
    }

    private function enterMatch(): void
    {
        $this->addStructuralAndIncreaseNesting();
    }

    private function leaveMatch(): void
    {
        $this->decreaseNesting();
    }

    private function enterClosure(): void
    {
        $this->nestingLevel++;
    }

    private function leaveClosure(): void
    {
        $this->nestingLevel--;
    }

    private function addStructuralAndIncreaseNesting(): void
    {
        $this->score += 1 + $this->nestingLevel;
        $this->structuralCount++;
        if ($this->nestingLevel > 0) {
            $this->nestingIncrementCount++;
        }
        $this->nestingLevel++;
    }

    private function addHybridAndIncreaseNesting(): void
    {
        $this->score += 1;
        $this->hybridCount++;
        $this->nestingLevel++;
    }

    private function addFundamental(): void
    {
        $this->score += 1;
        $this->fundamentalCount++;
    }

    private function decreaseNesting(): void
    {
        if ($this->nestingLevel > 0) {
            $this->nestingLevel--;
        }
    }

    private function countJumpStatement(Node $node): void
    {
        if ($node instanceof Stmt\Goto_) {
            $this->addFundamental();
            return;
        }

        if (!$node instanceof Stmt\Break_ && !$node instanceof Stmt\Continue_) {
            return;
        }

        if ($node->num !== null) {
            $this->addFundamental();
        }
    }

    private function countLogicalOperator(Node $node): void
    {
        if (!$this->isLogicalBinaryOp($node)) {
            return;
        }

        /** @var Expr\BinaryOp $node */
        if ($this->leftOperandIsSameLogicalOp($node)) {
            return;
        }

        $this->addFundamental();
    }

    private function isLogicalBinaryOp(Node $node): bool
    {
        return $node instanceof Expr\BinaryOp\BooleanAnd
            || $node instanceof Expr\BinaryOp\BooleanOr
            || $node instanceof Expr\BinaryOp\LogicalAnd
            || $node instanceof Expr\BinaryOp\LogicalOr;
    }

    private function leftOperandIsSameLogicalOp(Expr\BinaryOp $node): bool
    {
        $left = $node->left;
        $operatorClass = $node::class;

        return $left::class === $operatorClass;
    }

    private function countRecursion(Node $node): void
    {
        if ($this->hasRecursion || $this->currentMethodName === '') {
            return;
        }

        if ($node instanceof Expr\MethodCall) {
            $name = $this->resolveMethodName($node->name);
            if ($name === $this->currentMethodName) {
                $this->hasRecursion = true;
            }
            return;
        }

        if (!$node instanceof Expr\StaticCall) {
            return;
        }

        $name = $this->resolveMethodName($node->name);
        if ($name !== $this->currentMethodName) {
            return;
        }

        if ($this->isSelfStaticCall($node->class)) {
            $this->hasRecursion = true;
        }
    }

    private function resolveMethodName(Node\Expr|string|Node\Identifier $name): ?string
    {
        if ($name instanceof Node\Identifier) {
            return $name->toString();
        }

        if (is_string($name)) {
            return $name;
        }

        return null;
    }

    private function isSelfStaticCall(Node\Name|Expr $class): bool
    {
        if (!$class instanceof Node\Name) {
            return false;
        }

        $parts = $class->getParts();
        $last = end($parts);

        return in_array($last, ['self', 'static', 'parent'], true);
    }

    private function checkNamespaceLeave(Node $node): void
    {
        if ($node instanceof Stmt\Namespace_) {
            $this->currentNamespace = '';
        }
    }

    private function checkClassLeave(Node $node): void
    {
        if ($node instanceof Stmt\Class_ || $node instanceof Stmt\Trait_) {
            $this->currentClassName = '';
        }
    }
}
