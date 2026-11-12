<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

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
        public readonly bool $showUnderstandability = false,
        public readonly bool $groupByClass = false,
        public readonly bool $showDetailedCognitiveMetrics = true,
        public readonly ?CacheConfig $cache = null,
        /** @deprecated Never had an effect. Will be removed in 2.0. */
        public readonly ?PerformanceConfig $performance = null,
        public readonly array $customReporters = [],
        // Keeps CSV column positions backwards compatible for consumers that read columns by index.
        // Will be removed in the next major version, when the If Count columns move to their
        // logical position before If Nesting Level.
        public readonly bool $csvAppendIfCountColumns = false,
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
            'showUnderstandability' => $this->showUnderstandability,
            'groupByClass' => $this->groupByClass,
            'showDetailedCognitiveMetrics' => $this->showDetailedCognitiveMetrics,
            'cache' => $this->cache?->toArray(),
            'performance' => $this->performance?->toArray(),
            'customReporters' => $this->customReporters,
            'csvAppendIfCountColumns' => $this->csvAppendIfCountColumns,
        ];
    }
}
