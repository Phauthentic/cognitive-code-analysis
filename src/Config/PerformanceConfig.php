<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

/**
 * Configuration for performance-related settings.
 */
class PerformanceConfig
{
    public function __construct(
        public readonly int $batchSize = 100
    ) {
    }

    /**
     * Convert the performance configuration to an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'batchSize' => $this->batchSize,
        ];
    }
}
