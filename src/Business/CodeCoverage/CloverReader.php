<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Reads Clover XML coverage reports and provides coverage information by class
 */
class CloverReader implements CoverageReportReaderInterface
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

        $metricsNode = $this->getMetricsNode($classNode);
        if ($metricsNode === null) {
            return null;
        }

        $statements = (int)$metricsNode->getAttribute('statements');
        if ($statements === 0) {
            return 0.0;
        }

        $coveredStatements = (int)$metricsNode->getAttribute('coveredstatements');

        return $coveredStatements / $statements;
    }

    /**
     * Get branch coverage rate for a given class by FQCN
     * Note: Clover format doesn't provide meaningful branch coverage, returns 0.0
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

        $metricsNode = $this->getMetricsNode($classNode);
        if ($metricsNode === null) {
            return null;
        }

        $conditionals = (int)$metricsNode->getAttribute('conditionals');
        if ($conditionals === 0) {
            return 0.0;
        }

        $coveredConditionals = (int)$metricsNode->getAttribute('coveredconditionals');

        return $coveredConditionals / $conditionals;
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

        $metricsNode = $this->getMetricsNode($classNode);
        if ($metricsNode === null) {
            return null;
        }

        $complexity = $metricsNode->getAttribute('complexity');

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

        $metricsNode = $this->getMetricsNode($classNode);
        if ($metricsNode === null) {
            return null;
        }

        $statements = (int)$metricsNode->getAttribute('statements');
        $coveredStatements = (int)$metricsNode->getAttribute('coveredstatements');
        $lineRate = $statements > 0 ? $coveredStatements / $statements : 0.0;

        $conditionals = (int)$metricsNode->getAttribute('conditionals');
        $coveredConditionals = (int)$metricsNode->getAttribute('coveredconditionals');
        $branchRate = $conditionals > 0 ? $coveredConditionals / $conditionals : 0.0;

        return new CoverageDetails(
            name: $classNode->getAttribute('name'),
            filename: $this->getFilenameForClass($classNode),
            lineRate: $lineRate,
            branchRate: $branchRate,
            complexity: (int)$metricsNode->getAttribute('complexity'),
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
     * Get metrics node for a class
     */
    private function getMetricsNode(DOMElement $classNode): ?DOMElement
    {
        $metricsNodes = $this->xpath->query('./metrics', $classNode);

        if ($metricsNodes === false || $metricsNodes->length === 0) {
            return null;
        }

        $node = $metricsNodes->item(0);

        return $node instanceof DOMElement ? $node : null;
    }

    /**
     * Get filename for a class node
     */
    private function getFilenameForClass(DOMElement $classNode): string
    {
        $fileNode = $classNode->parentNode;
        if ($fileNode instanceof DOMElement && $fileNode->nodeName === 'file') {
            return $fileNode->getAttribute('name');
        }

        return '';
    }

    /**
     * Extract methods coverage information from a class node
     *
     * @return array<string, MethodCoverage>
     */
    private function extractMethodsCoverage(DOMElement $classNode): array
    {
        $methods = [];

        // In Clover format, methods are represented as <line type="method"> elements
        // within the parent <file> element
        $fileNode = $classNode->parentNode;
        if (!$fileNode instanceof DOMElement) {
            return [];
        }

        $methodLines = $this->xpath->query('.//line[@type="method"]', $fileNode);

        if ($methodLines === false) {
            return [];
        }

        foreach ($methodLines as $methodLine) {
            if ($methodLine instanceof DOMElement) {
                $methodName = $methodLine->getAttribute('name');
                $complexity = (int)$methodLine->getAttribute('complexity');

                // Calculate coverage for this method by looking at subsequent statement lines
                $methodCoverage = $this->calculateMethodCoverage($fileNode, $methodLine);

                $methods[$methodName] = new MethodCoverage(
                    name: $methodName,
                    lineRate: $methodCoverage,
                    branchRate: 0.0, // Clover doesn't provide per-method branch coverage
                    complexity: $complexity,
                );
            }
        }

        return $methods;
    }

    /**
     * Calculate coverage for a method by examining statement lines
     */
    private function calculateMethodCoverage(DOMElement $fileNode, DOMElement $methodLine): float
    {
        $methodLineNum = (int)$methodLine->getAttribute('num');
        $allLines = $this->xpath->query('.//line', $fileNode);

        if ($allLines === false) {
            return 0.0;
        }

        $statements = 0;
        $coveredStatements = 0;
        $inMethod = false;

        foreach ($allLines as $line) {
            if (!$line instanceof DOMElement) {
                continue;
            }

            $lineNum = (int)$line->getAttribute('num');
            $type = $line->getAttribute('type');

            // Check if this is the start of our method
            if ($lineNum === $methodLineNum && $type === 'method') {
                $inMethod = true;
                continue;
            }

            // Check if we've reached the next method (end of current method)
            if ($inMethod && $type === 'method') {
                break;
            }

            // Count statements in this method
            if ($inMethod && $type === 'stmt') {
                $statements++;
                $count = (int)$line->getAttribute('count');
                if ($count > 0) {
                    $coveredStatements++;
                }
            }
        }

        return $statements > 0 ? $coveredStatements / $statements : 0.0;
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
