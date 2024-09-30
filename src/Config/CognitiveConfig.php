<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Config;

/**
 *
 */
class CognitiveConfig
{
    public function __construct(
        public readonly array $excludeFilePatterns,
        public readonly array $excludePatterns,
        public readonly array $metrics
    ) {
    }
}
