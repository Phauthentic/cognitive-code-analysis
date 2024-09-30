<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Config;

/**
 *
 */
class HalsteadConfig
{
    public array $threshold;

    public function __construct(array $threshold)
    {
        $this->threshold = $threshold;
    }
}
