<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Cache;

use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use SplFileInfo;

/**
 * Interface for metrics cache strategies
 */
interface MetricsCacheStrategyInterface
{
    /**
     * Get cached metrics for a file if available
     *
     * @param SplFileInfo $file
     * @param string $configHash
     * @return array<string, mixed>|null
     */
    public function getCachedMetrics(SplFileInfo $file, string $configHash): ?array;

    /**
     * Cache metrics for a file
     *
     * @param SplFileInfo $file
     * @param array<string, mixed> $metrics
     * @param string $configHash
     * @param array<string, mixed> $ignoredItems
     */
    public function cacheMetrics(SplFileInfo $file, array $metrics, string $configHash, array $ignoredItems): void;

    /**
     * Generate configuration hash for cache invalidation
     *
     * @param CognitiveConfig $config
     * @return string
     */
    public function generateConfigHash(CognitiveConfig $config): string;

    /**
     * Clear all cached data
     */
    public function clear(): void;
}
