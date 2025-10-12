<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\CyclomaticComplexity;

class CyclomaticComplexityCalculator implements CyclomaticComplexityCalculatorInterface
{
    /**
     * Calculate total complexity from decision point counts.
     *
     * @param array<string, int> $decisionPointCounts Array of decision point counts
     * @return int Total cyclomatic complexity
     */
    public function calculateComplexity(array $decisionPointCounts): int
    {
        $baseComplexity = 1; // Base complexity for any method
        $totalComplexity = $baseComplexity;

        // Add complexity for each decision point type (excluding 'else' which doesn't add complexity)
        foreach ($decisionPointCounts as $type => $count) {
            if ($type === 'else') {
                continue;
            }

            $totalComplexity += $count;
        }

        return $totalComplexity;
    }

    /**
     * Create detailed breakdown of complexity factors.
     *
     * @param array<string, int> $decisionPointCounts Array of decision point counts
     * @param int $totalComplexity Total complexity value
     * @return array<string, int> Detailed breakdown including base complexity
     */
    public function createBreakdown(array $decisionPointCounts, int $totalComplexity): array
    {
        return array_merge([
            'total' => $totalComplexity,
            'base' => 1,
        ], $decisionPointCounts);
    }

    /**
     * Determine risk level based on complexity value.
     *
     * @param int $complexity The cyclomatic complexity value
     * @return string Risk level: 'low', 'medium', 'high', 'very_high'
     */
    public function getRiskLevel(int $complexity): string
    {
        return match (true) {
            $complexity <= 5 => 'low',
            $complexity <= 10 => 'medium',
            $complexity <= 15 => 'high',
            default => 'very_high',
        };
    }

    /**
     * Create complete summary with risk assessment.
     *
     * @param array<string, int> $classComplexities Class complexities indexed by class name
     * @param array<string, int> $methodComplexities Method complexities indexed by "ClassName::methodName"
     * @param array<string, array<string, int>> $methodBreakdowns Method breakdowns indexed by "ClassName::methodName"
     * @return array<string, mixed> Complete summary with risk assessment
     */
    public function createSummary(array $classComplexities, array $methodComplexities, array $methodBreakdowns): array
    {
        $summary = [
            'classes' => [],
            'methods' => [],
            'high_risk_methods' => [],
            'very_high_risk_methods' => [],
        ];

        // Class summary
        foreach ($classComplexities as $className => $complexity) {
            $summary['classes'][$className] = [
                'complexity' => $complexity,
                'risk_level' => $this->getRiskLevel($complexity),
            ];
        }

        // Method summary
        foreach ($methodComplexities as $methodKey => $complexity) {
            $riskLevel = $this->getRiskLevel($complexity);
            $summary['methods'][$methodKey] = [
                'complexity' => $complexity,
                'risk_level' => $riskLevel,
                'breakdown' => $methodBreakdowns[$methodKey] ?? [],
            ];

            if ($complexity >= 10) {
                $summary['high_risk_methods'][$methodKey] = $complexity;
            }
            if ($complexity < 15) {
                continue;
            }

            $summary['very_high_risk_methods'][$methodKey] = $complexity;
        }

        return $summary;
    }
}
