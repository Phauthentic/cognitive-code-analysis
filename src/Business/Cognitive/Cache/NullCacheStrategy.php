<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Cache;

use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use SplFileInfo;

/**
 * Null cache strategy that provides no caching functionality
 * Used when caching is disabled to provide zero overhead
 */
class NullCacheStrategy implements MetricsCacheStrategyInterface
{
    /**
     * Get cached metrics for a file if available
     * Always returns null since no caching is performed
     *
     * @param SplFileInfo $file
     * @param string $configHash
     * @return array<string, mixed>|null
     */
    public function getCachedMetrics(SplFileInfo $file, string $configHash): ?array
    {
        return null;
    }

    /**
     * Cache metrics for a file
     * No-op since no caching is performed
     *
     * @param SplFileInfo $file
     * @param array<string, mixed> $metrics
     * @param string $configHash
     * @param array<string, mixed> $ignoredItems
     */
    public function cacheMetrics(SplFileInfo $file, array $metrics, string $configHash, array $ignoredItems): void
    {
        // No-op - no caching performed
    }

    /**
     * Generate configuration hash for cache invalidation
     * Returns empty string since no caching is performed
     *
     * @param CognitiveConfig $config
     * @return string
     */
    public function generateConfigHash(CognitiveConfig $config): string
    {
        return '';
    }

    /**
     * Clear all cached data
     * No-op since no caching is performed
     */
    public function clear(): void
    {
        // No-op - no caching performed
    }
}
