<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage;

use DOMElement;

/**
 * Reads Cobertura XML coverage reports and provides coverage information by class
 */
class CoberturaReader extends AbstractXmlCoverageReader
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
            $lineRate = $classNode->getAttribute('line-rate');
            return $lineRate !== '' ? (float)$lineRate : null;
        });
    }

    /**
     * Get branch coverage rate for a given class by FQCN
     *
     * @param string $fqcn Fully Qualified Class Name
     * @return float|null Coverage rate (0.0 to 1.0) or null if class not found
     */
    public function getBranchCoverage(string $fqcn): ?float
    {
        return $this->withClassNode($fqcn, function (DOMElement $classNode) {
            $branchRate = $classNode->getAttribute('branch-rate');
            return $branchRate !== '' ? (float)$branchRate : null;
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
            $complexity = $classNode->getAttribute('complexity');
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
            return new CoverageDetails(
                name: $classNode->getAttribute('name'),
                filename: $classNode->getAttribute('filename'),
                lineRate: (float)$classNode->getAttribute('line-rate'),
                branchRate: (float)$classNode->getAttribute('branch-rate'),
                complexity: (int)$classNode->getAttribute('complexity'),
                methods: $this->extractMethodsCoverage($classNode),
            );
        });
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
            if (!($methodNode instanceof DOMElement)) {
                continue;
            }

            $methodName = $methodNode->getAttribute('name');
            $methods[$methodName] = new MethodCoverage(
                name: $methodName,
                lineRate: (float)$methodNode->getAttribute('line-rate'),
                branchRate: (float)$methodNode->getAttribute('branch-rate'),
                complexity: (int)$methodNode->getAttribute('complexity'),
            );
        }

        return $methods;
    }
}
