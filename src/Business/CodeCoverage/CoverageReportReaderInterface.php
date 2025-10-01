<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage;

/**
 * Interface for reading code coverage reports
 */
interface CoverageReportReaderInterface
{
    /**
     * Get line coverage rate for a given class by FQCN
     *
     * @param string $fqcn Fully Qualified Class Name
     * @return float|null Coverage rate (0.0 to 1.0) or null if class not found
     */
    public function getLineCoverage(string $fqcn): ?float;

    /**
     * Get branch coverage rate for a given class by FQCN
     *
     * @param string $fqcn Fully Qualified Class Name
     * @return float|null Coverage rate (0.0 to 1.0) or null if class not found
     */
    public function getBranchCoverage(string $fqcn): ?float;

    /**
     * Get complexity for a given class by FQCN
     *
     * @param string $fqcn Fully Qualified Class Name
     * @return int|null Complexity value or null if class not found
     */
    public function getComplexity(string $fqcn): ?int;

    /**
     * Get detailed coverage information for a given class
     *
     * @param string $fqcn Fully Qualified Class Name
     * @return CoverageDetails|null Coverage details or null if class not found
     */
    public function getCoverageDetails(string $fqcn): ?CoverageDetails;

    /**
     * Get all covered classes
     *
     * @return array List of FQCNs
     */
    public function getAllClasses(): array;
}
