<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

use InvalidArgumentException;

/**
 * @SuppressWarnings(BooleanArgumentFlag)
 * @SuppressWarnings(ExcessiveParameterList)
 */
class CognitiveConfig
{
    /**
     * @param array<string> $excludeFilePatterns
     * @param array<string> $excludePatterns
     * @param array<int|string, MetricsConfig> $metrics
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
        public readonly ?CacheConfig $cache = null,
    ) {
    }

    /**
     * Convert the cognitive configuration to an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $metricsArray = [];
        foreach ($this->metrics as $key => $metric) {
            $metricsArray[$key] = $metric->toArray();
        }

        return [
            'excludeFilePatterns' => $this->excludeFilePatterns,
            'excludePatterns' => $this->excludePatterns,
            'metrics' => $metricsArray,
            'showOnlyMethodsExceedingThreshold' => $this->showOnlyMethodsExceedingThreshold,
            'scoreThreshold' => $this->scoreThreshold,
            'showHalsteadComplexity' => $this->showHalsteadComplexity,
            'showCyclomaticComplexity' => $this->showCyclomaticComplexity,
            'groupByClass' => $this->groupByClass,
            'showDetailedCognitiveMetrics' => $this->showDetailedCognitiveMetrics,
            'cache' => $this->cache?->toArray(),
        ];
    }
}
