<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Halstead\HalsteadMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cyclomatic\CyclomaticMetrics;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

/**
 * Helper class for formatting metric values with colors and styling
 */
class MetricFormatter
{
    public function __construct(
        private readonly CognitiveConfig $config
    ) {
    }

    public function formatScore(float $score): string
    {
        return $score > $this->config->scoreThreshold
            ? '<error>' . $score . '</error>'
            : '<info>' . $score . '</info>';
    }

    public function formatHalsteadVolume(?HalsteadMetrics $halstead): string
    {
        if (!$halstead) {
            return '-';
        }

        $value = round($halstead->getVolume(), 3);

        return match (true) {
            $value >= 1000 => '<error>' . $value . '</error>',
            $value >= 100 => '<comment>' . $value . '</comment>',
            default => (string)$value,
        };
    }

    public function formatHalsteadDifficulty(?HalsteadMetrics $halstead): string
    {
        if (!$halstead) {
            return '-';
        }
        $value = round($halstead->difficulty, 3);

        return match (true) {
            $value >= 50 => '<error>' . $value . '</error>',
            $value >= 10 => '<comment>' . $value . '</comment>',
            default => (string)$value,
        };
    }

    public function formatHalsteadEffort(?HalsteadMetrics $halstead): string
    {
        if (!$halstead) {
            return '-';
        }
        $value = round($halstead->effort, 3);

        return match (true) {
            $value >= 5000 => '<error>' . $value . '</error>',
            $value >= 500 => '<comment>' . $value . '</comment>',
            default => (string)$value,
        };
    }

    public function formatCyclomaticComplexity(?CyclomaticMetrics $cyclomatic): string
    {
        if (!$cyclomatic) {
            return '-';
        }
        $complexity = $cyclomatic->complexity;
        $risk = $cyclomatic->riskLevel ?? '';
        if ($risk === '') {
            return (string)$complexity;
        }
        $riskColored = $this->colorCyclomaticRisk($risk);
        return $complexity . ' (' . $riskColored . ')';
    }

    private function colorCyclomaticRisk(string $risk): string
    {
        return match (strtolower($risk)) {
            'medium' => '<comment>' . $risk . '</comment>',
            'high' => '<error>' . $risk . '</error>',
            default => $risk,
        };
    }
}
