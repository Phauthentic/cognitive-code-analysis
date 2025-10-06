<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

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

    /**
     * Convert the metrics configuration to an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'threshold' => $this->threshold,
            'scale' => $this->scale,
            'enabled' => $this->enabled,
        ];
    }
}
