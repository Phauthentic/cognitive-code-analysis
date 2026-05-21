<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Understandability;

interface UnderstandabilityCalculatorInterface
{
    /**
     * @param array<string, int> $incrementCounts
     */
    public function calculateComplexity(array $incrementCounts): int;

    /**
     * @param array<string, int> $incrementCounts
     * @return array<string, int>
     */
    public function createBreakdown(array $incrementCounts, int $totalComplexity): array;

    public function getRiskLevel(int $complexity): string;

    /**
     * @param array<string, int> $methodComplexities
     * @param array<string, array<string, int>> $methodBreakdowns
     * @return array<string, mixed>
     */
    public function createSummary(array $methodComplexities, array $methodBreakdowns): array;
}
