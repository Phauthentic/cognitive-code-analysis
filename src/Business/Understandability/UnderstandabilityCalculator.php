<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Understandability;

class UnderstandabilityCalculator implements UnderstandabilityCalculatorInterface
{
    /**
     * @param array<string, int> $incrementCounts
     */
    public function calculateComplexity(array $incrementCounts): int
    {
        return $incrementCounts['total'] ?? 0;
    }

    /**
     * @param array<string, int> $incrementCounts
     * @return array<string, int>
     */
    public function createBreakdown(array $incrementCounts, int $totalComplexity): array
    {
        return array_merge(['total' => $totalComplexity], $incrementCounts);
    }

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
     * @param array<string, int> $methodComplexities
     * @param array<string, array<string, int>> $methodBreakdowns
     * @return array<string, mixed>
     */
    public function createSummary(array $methodComplexities, array $methodBreakdowns): array
    {
        $summary = [
            'methods' => [],
            'high_risk_methods' => [],
            'very_high_risk_methods' => [],
        ];

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

            if ($complexity >= 15) {
                $summary['very_high_risk_methods'][$methodKey] = $complexity;
            }
        }

        return $summary;
    }
}
