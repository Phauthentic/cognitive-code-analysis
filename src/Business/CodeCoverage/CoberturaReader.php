<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Reads Cobertura XML coverage reports and provides coverage information by class
 */
class CoberturaReader implements CoverageReportReaderInterface
{
    private DOMDocument $document;
    private DOMXPath $xpath;
    /** @var array<string, DOMElement|null> */
    private array $cache = [];

    public function __construct(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new CognitiveAnalysisException("Coverage file not found: {$filePath}");
        }

        $this->document = new DOMDocument();
        if (!@$this->document->load($filePath)) {
            throw new CognitiveAnalysisException("Failed to parse coverage XML file: {$filePath}");
        }

        $this->xpath = new DOMXPath($this->document);
    }

    /**
     * Get line coverage rate for a given class by FQCN
     *
     * @param string $fqcn Fully Qualified Class Name
     * @return float|null Coverage rate (0.0 to 1.0) or null if class not found
     */
    public function getLineCoverage(string $fqcn): ?float
    {
        $classNode = $this->findClassNode($fqcn);
        if ($classNode === null) {
            return null;
        }

        $lineRate = $classNode->getAttribute('line-rate');

        return $lineRate !== '' ? (float)$lineRate : null;
    }

    /**
     * Get branch coverage rate for a given class by FQCN
     *
     * @param string $fqcn Fully Qualified Class Name
     * @return float|null Coverage rate (0.0 to 1.0) or null if class not found
     */
    public function getBranchCoverage(string $fqcn): ?float
    {
        $classNode = $this->findClassNode($fqcn);
        if ($classNode === null) {
            return null;
        }

        $branchRate = $classNode->getAttribute('branch-rate');

        return $branchRate !== '' ? (float)$branchRate : null;
    }

    /**
     * Get complexity for a given class by FQCN
     *
     * @param string $fqcn Fully Qualified Class Name
     * @return int|null Complexity value or null if class not found
     */
    public function getComplexity(string $fqcn): ?int
    {
        $classNode = $this->findClassNode($fqcn);
        if ($classNode === null) {
            return null;
        }

        $complexity = $classNode->getAttribute('complexity');

        return $complexity !== '' ? (int)$complexity : null;
    }

    /**
     * Get detailed coverage information for a given class
     *
     * @param string $fqcn Fully Qualified Class Name
     * @return CoverageDetails|null Coverage details or null if class not found
     */
    public function getCoverageDetails(string $fqcn): ?CoverageDetails
    {
        $classNode = $this->findClassNode($fqcn);
        if ($classNode === null) {
            return null;
        }

        return new CoverageDetails(
            name: $classNode->getAttribute('name'),
            filename: $classNode->getAttribute('filename'),
            lineRate: (float)$classNode->getAttribute('line-rate'),
            branchRate: (float)$classNode->getAttribute('branch-rate'),
            complexity: (int)$classNode->getAttribute('complexity'),
            methods: $this->extractMethodsCoverage($classNode),
        );
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
     * Find a class node by FQCN
     */
    private function findClassNode(string $fqcn): ?DOMElement
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
     * Extract methods coverage information from a class node
     *
     * @return array<string, MethodCoverage>
     */
    private function extractMethodsCoverage(DOMElement $classNode): array
    {
        $methods = [];
        $methodNodes = $this->xpath->query('.//method', $classNode);

        if ($methodNodes === false) {
            return [];
        }

        foreach ($methodNodes as $methodNode) {
            if ($methodNode instanceof DOMElement) {
                $methodName = $methodNode->getAttribute('name');
                $methods[$methodName] = new MethodCoverage(
                    name: $methodName,
                    lineRate: (float)$methodNode->getAttribute('line-rate'),
                    branchRate: (float)$methodNode->getAttribute('branch-rate'),
                    complexity: (int)$methodNode->getAttribute('complexity'),
                );
            }
        }

        return $methods;
    }

    /**
     * Escape XPath value for query
     */
    private function escapeXPathValue(string $value): string
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
