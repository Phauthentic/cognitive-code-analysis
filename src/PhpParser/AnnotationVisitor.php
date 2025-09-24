<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\PhpParser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Node visitor to detect and track @cca-ignore annotations.
 *
 * This visitor scans for @cca-ignore annotations in docblocks and comments
 * and maintains a list of ignored classes and methods.
 */
class AnnotationVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<string, string> List of ignored classes with their FQCN
     */
    private array $ignoredClasses = [];

    /**
     * @var array<string, string> List of ignored methods with their FQCN::methodName
     */
    private array $ignoredMethods = [];

    private string $currentNamespace = '';
    private string $currentClassName = '';

    /**
     * Check if a node has the @cca-ignore annotation.
     */
    private function hasIgnoreAnnotation(Node $node): bool
    {
        // Check doc comment first
        $docComment = $node->getDocComment();
        if ($docComment !== null) {
            if (str_contains($docComment->getText(), '@cca-ignore')) {
                return true;
            }
        }

        // Check regular comments
        $comments = $node->getComments();
        foreach ($comments as $comment) {
            if (str_contains($comment->getText(), '@cca-ignore')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set the current namespace context.
     */
    private function setCurrentNamespace(Node $node): void
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = $node->name instanceof Node\Name ? $node->name->toString() : '';
        }
    }

    /**
     * Set the current class context.
     */
    private function setCurrentClass(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Trait_) {
            if ($node->name !== null) {
                $fqcn = $this->currentNamespace . '\\' . $node->name->toString();
                $this->currentClassName = $this->normalizeFqcn($fqcn);
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

    /**
     * Process class/trait nodes to check for @cca-ignore annotations.
     */
    private function processClassNode(Node $node): void
    {
        if (!$node instanceof Node\Stmt\Class_ && !$node instanceof Node\Stmt\Trait_) {
            return;
        }

        if ($this->hasIgnoreAnnotation($node)) {
            $this->ignoredClasses[$this->currentClassName] = $this->currentClassName;
        }
    }

    /**
     * Process method nodes to check for @cca-ignore annotations.
     */
    private function processMethodNode(Node $node): void
    {
        if (!$node instanceof Node\Stmt\ClassMethod) {
            return;
        }

        // Skip methods that don't have a class context
        if (empty($this->currentClassName)) {
            return;
        }

        if ($this->hasIgnoreAnnotation($node)) {
            $methodKey = $this->currentClassName . '::' . $node->name->toString();
            $this->ignoredMethods[$methodKey] = $methodKey;
        }
    }

    public function enterNode(Node $node): void
    {
        $this->setCurrentNamespace($node);
        $this->setCurrentClass($node);
        $this->processClassNode($node);
        $this->processMethodNode($node);
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->currentNamespace = '';
        }

        if ($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Trait_) {
            $this->currentClassName = '';
        }
    }

    /**
     * Get all ignored classes.
     *
     * @return array<string, string> Array of ignored class FQCNs
     */
    public function getIgnoredClasses(): array
    {
        return $this->ignoredClasses;
    }

    /**
     * Get all ignored methods.
     *
     * @return array<string, string> Array of ignored method keys (ClassName::methodName)
     */
    public function getIgnoredMethods(): array
    {
        return $this->ignoredMethods;
    }

    /**
     * Get all ignored items (both classes and methods).
     *
     * @return array<string, array<string, string>> Array with 'classes' and 'methods' keys
     */
    public function getIgnored(): array
    {
        return [
            'classes' => $this->ignoredClasses,
            'methods' => $this->ignoredMethods,
        ];
    }

    /**
     * Check if a specific class is ignored.
     */
    public function isClassIgnored(string $className): bool
    {
        return isset($this->ignoredClasses[$className]);
    }

    /**
     * Check if a specific method is ignored.
     */
    public function isMethodIgnored(string $methodKey): bool
    {
        return isset($this->ignoredMethods[$methodKey]);
    }

    /**
     * Reset the visitor state.
     */
    public function reset(): void
    {
        $this->ignoredClasses = [];
        $this->ignoredMethods = [];
        $this->currentNamespace = '';
        $this->currentClassName = '';
    }

    /**
     * Reset only the current context (for between-file cleanup).
     */
    public function resetContext(): void
    {
        $this->currentNamespace = '';
        $this->currentClassName = '';
    }
}
