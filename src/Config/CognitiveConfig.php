<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

/**
 * @SuppressWarnings(BooleanArgumentFlag)
 */
class CognitiveConfig
{
    /**
     * @param array<string> $excludeFilePatterns
     * @param array<string> $excludePatterns
     * @param array<int|string, MetricsConfig> $metrics
     * @param array<string, array<string, mixed>> $customReporters
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function __construct(
        public readonly array $excludeFilePatterns,
        public readonly array $excludePatterns,
        public readonly array $metrics,
        public readonly bool $showOnlyMethodsExceedingThreshold,
        public readonly float $scoreThreshold,
        public readonly bool $showHalsteadComplexity = false,
        public readonly bool $showCyclomaticComplexity = false,
        public readonly bool $groupByClass = false,
        public readonly bool $showDetailedCognitiveMetrics = true,
        public readonly array $customReporters = [],
    ) {
    }
}
