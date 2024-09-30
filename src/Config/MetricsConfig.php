<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Config;

/**
 *
 */
class MetricsConfig
{
    public function __construct(
        public readonly int $threshold,
        public readonly float $scale,
        public readonly bool $enabled
    ) {
    }
}
