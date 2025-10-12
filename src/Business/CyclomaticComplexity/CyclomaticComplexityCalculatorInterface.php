<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\CyclomaticComplexity;

interface CyclomaticComplexityCalculatorInterface
{
    /**
     * Calculate total complexity from decision point counts.
     *
     * @param array<string, int> $decisionPointCounts Array of decision point counts
     * @return int Total cyclomatic complexity
     */
    public function calculateComplexity(array $decisionPointCounts): int;

    /**
     * Create detailed breakdown of complexity factors.
     *
     * @param array<string, int> $decisionPointCounts Array of decision point counts
     * @param int $totalComplexity Total complexity value
     * @return array<string, int> Detailed breakdown including base complexity
     */
    public function createBreakdown(array $decisionPointCounts, int $totalComplexity): array;

    /**
     * Determine risk level based on complexity value.
     *
     * @param int $complexity The cyclomatic complexity value
     * @return string Risk level: 'low', 'medium', 'high', 'very_high'
     */
    public function getRiskLevel(int $complexity): string;

    /**
     * Create complete summary with risk assessment.
     *
     * @param array<string, int> $classComplexities Class complexities indexed by class name
     * @param array<string, int> $methodComplexities Method complexities indexed by "ClassName::methodName"
     * @param array<string, array<string, int>> $methodBreakdowns Method breakdowns indexed by "ClassName::methodName"
     * @return array<string, mixed> Complete summary with risk assessment
     */
    public function createSummary(array $classComplexities, array $methodComplexities, array $methodBreakdowns): array;
}
