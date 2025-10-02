<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Abstract base class for XML-based coverage report readers
 */
abstract class AbstractXmlCoverageReader implements CoverageReportReaderInterface
{
    protected DOMDocument $document;
    protected DOMXPath $xpath;

    /** @var array<string, DOMElement|null> */
    protected array $cache = [];

    public function __construct(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new CognitiveAnalysisException("Coverage file not found: {$filePath}");
        }

        $this->document = new DOMDocument();

        libxml_use_internal_errors(true);
        $loaded = $this->document->load($filePath);
        libxml_use_internal_errors(false);

        if (!$loaded) {
            throw new CognitiveAnalysisException("Failed to parse coverage XML file: {$filePath}");
        }

        $this->xpath = new DOMXPath($this->document);
    }

    /**
     * Get all covered classes
     *
     * @return array<string> List of FQCNs
     */
    public function getAllClasses(): array
    {
        $classes = $this->xpath->query('//class');
        $fqcns = [];

        if ($classes === false) {
            return [];
        }

        foreach ($classes as $class) {
            if ($class instanceof DOMElement) {
                $fqcns[] = $class->getAttribute('name');
            }
        }

        return $fqcns;
    }

    /**
     * Execute callback with class node, returning null if class not found
     *
     * @template T
     * @param string $fqcn Fully Qualified Class Name
     * @param callable(DOMElement): T $callback Callback to execute with class node
     * @return T|null Result from callback or null if class not found
     */
    protected function withClassNode(string $fqcn, callable $callback): mixed
    {
        $classNode = $this->findClassNode($fqcn);
        if ($classNode === null) {
            return null;
        }
        return $callback($classNode);
    }

    /**
     * Find a class node by FQCN
     */
    protected function findClassNode(string $fqcn): ?DOMElement
    {
        if (isset($this->cache[$fqcn])) {
            return $this->cache[$fqcn];
        }

        $escapedFqcn = $this->escapeXPathValue($fqcn);
        $query = "//class[@name={$escapedFqcn}]";
        $nodes = $this->xpath->query($query);

        if ($nodes === false || $nodes->length === 0) {
            $this->cache[$fqcn] = null;
            return null;
        }

        $node = $nodes->item(0);
        if (!$node instanceof DOMElement) {
            $this->cache[$fqcn] = null;
            return null;
        }

        $this->cache[$fqcn] = $node;

        return $this->cache[$fqcn];
    }

    /**
     * Escape XPath value for query
     */
    protected function escapeXPathValue(string $value): string
    {
        if (strpos($value, "'") === false) {
            return "'{$value}'";
        }

        if (strpos($value, '"') === false) {
            return "\"{$value}\"";
        }

        // Value contains both single and double quotes
        $parts = explode("'", $value);
        $escapedParts = array_map(fn($part) => "'{$part}'", $parts);

        return 'concat(' . implode(', "\'", ', $escapedParts) . ')';
    }
}
