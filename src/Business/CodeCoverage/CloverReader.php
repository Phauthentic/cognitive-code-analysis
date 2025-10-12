<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage;

use DOMElement;

/**
 * Reads Clover XML coverage reports and provides coverage information by class
 *
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
class CloverReader extends AbstractXmlCoverageReader
{
    /**
     * Get line coverage rate for a given class by FQCN
     *
     * @param string $fqcn Fully Qualified Class Name
     * @return float|null Coverage rate (0.0 to 1.0) or null if class not found
     */
    public function getLineCoverage(string $fqcn): ?float
    {
        return $this->withClassNode($fqcn, function (DOMElement $classNode) {
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
        });
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
        return $this->withClassNode($fqcn, function (DOMElement $classNode) {
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
        });
    }

    /**
     * Get complexity for a given class by FQCN
     *
     * @param string $fqcn Fully Qualified Class Name
     * @return int|null Complexity value or null if class not found
     */
    public function getComplexity(string $fqcn): ?int
    {
        return $this->withClassNode($fqcn, function (DOMElement $classNode) {
            $metricsNode = $this->getMetricsNode($classNode);
            if ($metricsNode === null) {
                return null;
            }

            $complexity = $metricsNode->getAttribute('complexity');

            return $complexity !== '' ? (int)$complexity : null;
        });
    }

    /**
     * Get detailed coverage information for a given class
     *
     * @param string $fqcn Fully Qualified Class Name
     * @return CoverageDetails|null Coverage details or null if class not found
     */
    public function getCoverageDetails(string $fqcn): ?CoverageDetails
    {
        return $this->withClassNode($fqcn, function (DOMElement $classNode) {
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
        });
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
            if (!($methodLine instanceof DOMElement)) {
                continue;
            }

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

        $coverage = $this->extractMethodCoverageFromLines($allLines, $methodLineNum);

        return $coverage['statements'] > 0
            ? $coverage['covered'] / $coverage['statements']
            : 0.0;
    }

    /**
     * Extract coverage statistics from lines for a specific method
     *
     * @return array{statements: int, covered: int}
     */
    private function extractMethodCoverageFromLines(mixed $allLines, int $methodLineNum): array
    {
        $statements = 0;
        $coveredStatements = 0;
        $inMethod = false;

        foreach ($allLines as $line) {
            if (!$line instanceof DOMElement) {
                continue;
            }

            $lineNum = (int)$line->getAttribute('num');
            $type = $line->getAttribute('type');

            if ($this->isMethodStart($lineNum, $type, $methodLineNum)) {
                $inMethod = true;
                continue;
            }

            if ($this->isMethodEnd($inMethod, $type)) {
                break;
            }

            if (!$this->isMethodStatement($inMethod, $type)) {
                continue;
            }

            $statements++;
            if (!$this->isStatementCovered($line)) {
                continue;
            }

            $coveredStatements++;
        }

        return [
            'statements' => $statements,
            'covered' => $coveredStatements,
        ];
    }

    private function isMethodStart(int $lineNum, string $type, int $targetLineNum): bool
    {
        return $lineNum === $targetLineNum && $type === 'method';
    }

    private function isMethodEnd(bool $inMethod, string $type): bool
    {
        return $inMethod && $type === 'method';
    }

    private function isMethodStatement(bool $inMethod, string $type): bool
    {
        return $inMethod && $type === 'stmt';
    }

    private function isStatementCovered(DOMElement $line): bool
    {
        $count = (int)$line->getAttribute('count');
        return $count > 0;
    }
}
