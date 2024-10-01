<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

/**
 *
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
        public readonly array $metrics
    ) {
    }
}
