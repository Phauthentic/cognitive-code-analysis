<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage;

/**
 * Value object representing coverage information for a single method
 */
class MethodCoverage
{
    private string $name;
    private float $lineRate;
    private float $branchRate;
    private int $complexity;

    public function __construct(
        string $name,
        float $lineRate,
        float $branchRate,
        int $complexity,
    ) {
        $this->name = $name;
        $this->lineRate = $lineRate;
        $this->branchRate = $branchRate;
        $this->complexity = $complexity;
    }

    public function getName(): string
    {
        return $this->name;
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
}
