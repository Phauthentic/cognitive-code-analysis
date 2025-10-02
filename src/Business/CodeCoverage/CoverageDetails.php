<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage;

/**
 * Value object representing detailed coverage information for a class
 */
class CoverageDetails
{
    private string $name;
    private string $filename;
    private float $lineRate;
    private float $branchRate;
    private int $complexity;
    /** @var array<string, MethodCoverage> */
    private array $methods;

    /**
     * @param string $name Fully qualified class name
     * @param string $filename File path relative to source
     * @param float $lineRate Line coverage rate (0.0 to 1.0)
     * @param float $branchRate Branch coverage rate (0.0 to 1.0)
     * @param int $complexity Complexity value
     * @param array<string, MethodCoverage> $methods Method coverage indexed by method name
     */
    public function __construct(
        string $name,
        string $filename,
        float $lineRate,
        float $branchRate,
        int $complexity,
        array $methods,
    ) {
        $this->name = $name;
        $this->filename = $filename;
        $this->lineRate = $lineRate;
        $this->branchRate = $branchRate;
        $this->complexity = $complexity;
        $this->methods = $methods;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getLineRate(): float
    {
        return $this->lineRate;
    }

    public function getBranchRate(): float
    {
        return $this->branchRate;
    }

    public function getComplexity(): int
    {
        return $this->complexity;
    }

    /**
     * @return array<string, MethodCoverage>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }
}
