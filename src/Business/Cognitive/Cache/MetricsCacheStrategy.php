<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Cache;

use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Psr\Cache\CacheItemPoolInterface;
use SplFileInfo;

/**
 * Active cache strategy that stores metrics in cache
 */
class MetricsCacheStrategy implements MetricsCacheStrategyInterface
{
    public function __construct(
        private readonly CacheItemPoolInterface $cachePool,
    ) {
    }

    /**
     * Get cached metrics for a file if available
     *
     * @param SplFileInfo $file
     * @param string $configHash
     * @return array<string, mixed>|null
     */
    public function getCachedMetrics(SplFileInfo $file, string $configHash): ?array
    {
        $cacheKey = $this->generateCacheKey($file, $configHash);
        $cacheItem = $this->cachePool->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            return null;
        }

        $cachedData = $cacheItem->get();
        return $cachedData['analysis_result'] ?? null;
    }

    /**
     * Cache metrics for a file
     *
     * @param SplFileInfo $file
     * @param array<string, mixed> $metrics
     * @param string $configHash
     * @param array<string, mixed> $ignoredItems
     */
    public function cacheMetrics(SplFileInfo $file, array $metrics, string $configHash, array $ignoredItems): void
    {
        $cacheKey = $this->generateCacheKey($file, $configHash);
        $cacheItem = $this->cachePool->getItem($cacheKey);

        $cacheItem->set([
            'version' => '1.0',
            'file_path' => $file->getRealPath(),
            'file_mtime' => $file->getMTime(),
            'config_hash' => $configHash,
            'analysis_result' => $metrics,
            'ignored_items' => $ignoredItems,
            'cached_at' => time()
        ]);

        $this->cachePool->save($cacheItem);
    }

    /**
     * Generate configuration hash for cache invalidation
     *
     * @param CognitiveConfig $config
     * @return string
     */
    public function generateConfigHash(CognitiveConfig $config): string
    {
        return md5(serialize($config->toArray()));
    }

    /**
     * Clear all cached data
     */
    public function clear(): void
    {
        $this->cachePool->clear();
    }

    /**
     * Generate a cache key for a file based on path, modification time, and config hash
     */
    private function generateCacheKey(SplFileInfo $file, string $configHash): string
    {
        $filePath = $file->getRealPath();
        $fileMtime = $file->getMTime();

        return 'phpcca_' . md5($filePath . '|' . $fileMtime . '|' . $configHash);
    }
}
