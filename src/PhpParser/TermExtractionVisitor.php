<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\PhpParser;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

/**
 * Node visitor to extract variable, property, and parameter names for semantic analysis.
 */
class TermExtractionVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<string> Collected identifiers
     */
    private array $identifiers = [];

    /**
     * @var string Current file being processed
     */
    private string $currentFile = '';

    /**
     * @var string Current class being processed
     */
    private string $currentClass = '';

    /**
     * @var string Current method being processed
     */
    private string $currentMethod = '';

    /**
     * @var array<string, array<string>> Identifiers grouped by file
     */
    private array $fileIdentifiers = [];

    /**
     * @var array<string, array<string>> Identifiers grouped by class
     */
    private array $classIdentifiers = [];

    public function enterNode(Node $node): ?int
    {
        $this->extractIdentifiersFromNode($node);
        return null;
    }

    /**
     * Extract identifiers from various node types.
     */
    private function extractIdentifiersFromNode(Node $node): void
    {
        switch ($node->getType()) {
            case 'Stmt_Class':
                $this->handleClassNode($node);
                break;
            case 'Stmt_ClassMethod':
                $this->handleMethodNode($node);
                break;
            case 'Expr_Variable':
                $this->handleVariableNode($node);
                break;
            case 'Stmt_Property':
                $this->handlePropertyNode($node);
                break;
            case 'Param':
                $this->handleParameterNode($node);
                break;
            case 'Expr_PropertyFetch':
                $this->handlePropertyFetchNode($node);
                break;
            case 'Expr_StaticPropertyFetch':
                $this->handleStaticPropertyFetchNode($node);
                break;
        }
    }

    /**
     * Handle class declaration.
     */
    private function handleClassNode(Node $node): void
    {
        if (isset($node->name)) {
            $this->currentClass = $node->name->toString();
            $this->addIdentifier($node->name->toString());
        }
    }

    /**
     * Handle method declaration.
     */
    private function handleMethodNode(Node $node): void
    {
        if (isset($node->name)) {
            $this->currentMethod = $node->name->toString();
            $this->addIdentifier($node->name->toString());
        }
    }

    /**
     * Handle variable usage.
     */
    private function handleVariableNode(Node $node): void
    {
        if (isset($node->name) && is_string($node->name)) {
            $this->addIdentifier($node->name);
        }
    }

    /**
     * Handle property declaration.
     */
    private function handlePropertyNode(Node $node): void
    {
        foreach ($node->props as $prop) {
            if (isset($prop->name)) {
                $this->addIdentifier($prop->name->toString());
            }
        }
    }

    /**
     * Handle parameter declaration.
     */
    private function handleParameterNode(Node $node): void
    {
        if (isset($node->var) && isset($node->var->name)) {
            $this->addIdentifier($node->var->name);
        }
    }

    /**
     * Handle property access (object->property).
     */
    private function handlePropertyFetchNode(Node $node): void
    {
        if (isset($node->name)) {
            $this->addIdentifier($node->name->toString());
        }
    }

    /**
     * Handle static property access (Class::$property).
     */
    private function handleStaticPropertyFetchNode(Node $node): void
    {
        if (isset($node->name)) {
            $this->addIdentifier($node->name->toString());
        }
    }

    /**
     * Add an identifier to the collection.
     */
    private function addIdentifier(string $identifier): void
    {
        // Skip empty identifiers
        if (empty($identifier)) {
            return;
        }

        // Skip common technical identifiers
        if ($this->isTechnicalIdentifier($identifier)) {
            return;
        }

        $this->identifiers[] = $identifier;

        // Add to file-specific collection
        if (!empty($this->currentFile)) {
            $this->fileIdentifiers[$this->currentFile][] = $identifier;
        }

        // Add to class-specific collection
        if (!empty($this->currentClass)) {
            $this->classIdentifiers[$this->currentClass][] = $identifier;
        }
    }

    /**
     * Check if an identifier is a common technical term.
     */
    private function isTechnicalIdentifier(string $identifier): bool
    {
        $technicalTerms = [
            'this', 'self', 'parent', 'static', 'null', 'true', 'false',
            'array', 'string', 'int', 'float', 'bool', 'object', 'mixed',
            'void', 'never', 'callable', 'iterable', 'resource'
        ];

        return in_array(strtolower($identifier), $technicalTerms, true);
    }

    /**
     * Set the current file being processed.
     */
    public function setCurrentFile(string $file): void
    {
        $this->currentFile = $file;
    }

    /**
     * Get all collected identifiers.
     *
     * @return array<string>
     */
    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    /**
     * Get identifiers grouped by file.
     *
     * @return array<string, array<string>>
     */
    public function getFileIdentifiers(): array
    {
        return $this->fileIdentifiers;
    }

    /**
     * Get identifiers grouped by class.
     *
     * @return array<string, array<string>>
     */
    public function getClassIdentifiers(): array
    {
        return $this->classIdentifiers;
    }

    /**
     * Get identifiers for a specific file.
     *
     * @param string $file
     * @return array<string>
     */
    public function getIdentifiersForFile(string $file): array
    {
        return $this->fileIdentifiers[$file] ?? [];
    }

    /**
     * Get identifiers for a specific class.
     *
     * @param string $class
     * @return array<string>
     */
    public function getIdentifiersForClass(string $class): array
    {
        return $this->classIdentifiers[$class] ?? [];
    }

    /**
     * Clear all collected data.
     */
    public function clear(): void
    {
        $this->identifiers = [];
        $this->fileIdentifiers = [];
        $this->classIdentifiers = [];
        $this->currentFile = '';
        $this->currentClass = '';
        $this->currentMethod = '';
    }

    /**
     * Reset for new file processing.
     */
    public function resetForNewFile(): void
    {
        $this->currentFile = '';
        $this->currentClass = '';
        $this->currentMethod = '';
    }
}
