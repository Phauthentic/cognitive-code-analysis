<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Config;

/**
 *
 */
class HalsteadConfig
{
    /**
     * @param array<string, float> $threshold
     */
    public function __construct(
        public readonly array $threshold
    ) {
    }
}
