<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\FileProcessed;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\ParserFailed;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\SourceFilesFound;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\DirectoryScanner;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use SplFileInfo;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

/**
 * CognitiveMetricsCollector class that collects cognitive metrics from source files
 */
class CognitiveMetricsCollector
{
    /**
     * @var array<string, mixed>
     */
    private array $ignoredItems = [];

    public function __construct(
        protected readonly Parser $parser,
        protected readonly DirectoryScanner $directoryScanner,
        protected readonly ConfigService $configService,
        protected readonly MessageBusInterface $messageBus,
        protected readonly CacheItemPoolInterface $cachePool,
    ) {
    }

    /**
     * Collect cognitive metrics from the given path
     *
     * @param string $path
     * @param CognitiveConfig $config
     * @return CognitiveMetricsCollection
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException|\InvalidArgumentException
     */
    public function collect(string $path, CognitiveConfig $config): CognitiveMetricsCollection
    {
        return $this->collectFromPaths([$path], $config);
    }

    /**
     * Collect cognitive metrics from multiple paths and merge them into a single collection
     *
     * @param array<string> $paths Array of paths to process
     * @param CognitiveConfig $config
     * @return CognitiveMetricsCollection Merged collection of metrics from all paths
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException|\InvalidArgumentException
     */
    public function collectFromPaths(array $paths, CognitiveConfig $config): CognitiveMetricsCollection
    {
        $allFiles = [];

        foreach ($paths as $path) {
            $files = $this->findSourceFiles($path, $config->excludeFilePatterns);
            $allFiles = array_merge($allFiles, iterator_to_array($files));
        }

        $this->messageBus->dispatch(new SourceFilesFound(array_values($allFiles)));

        return $this->findMetrics($allFiles);
    }

    /**
     * @throws CognitiveAnalysisException
     */
    private function getCodeFromFile(SplFileInfo $file): string
    {
        $code = file_get_contents($file->getRealPath());

        if ($code === false) {
            throw new CognitiveAnalysisException("Could not read file: {$file->getRealPath()}");
        }

        return $code;
    }

    /**
     * Collect metrics from the found source files
     *
     * @param iterable<SplFileInfo> $files
     * @return CognitiveMetricsCollection
     * @throws \InvalidArgumentException
     * @throws \InvalidArgumentException
     */
    private function findMetrics(iterable $files): CognitiveMetricsCollection
    {
        $metricsCollection = new CognitiveMetricsCollection();
        $fileCount = 0;
        $config = $this->configService->getConfig();
        $configHash = $this->generateConfigHash($config);
        $useCache = $config->cache?->enabled === true;

        foreach ($files as $file) {
            // Try to get cached metrics
            $cached = $this->getCachedMetrics($file, $configHash, $useCache);
            $metrics = $cached['metrics'];

            // If not cached, process the file
            if ($metrics === null) {
                $metrics = $this->processFile($file, $fileCount, $cached['cacheItem'], $useCache, $configHash);

                if ($metrics === null) {
                    continue;
                }
            }

            $metricsCollection = $this->processMethodMetrics(
                $metrics,
                $metricsCollection,
                $this->normalizeFilename($file)
            );
        }

        return $metricsCollection;
    }

    /**
     * @param array<string, mixed> $methodMetrics
     * @param CognitiveMetricsCollection $metricsCollection
     * @param string $file
     * @return CognitiveMetricsCollection
     */
    private function processMethodMetrics(
        array $methodMetrics,
        CognitiveMetricsCollection $metricsCollection,
        string $file
    ): CognitiveMetricsCollection {
        foreach ($methodMetrics as $classAndMethod => $metrics) {
            if ($this->isExcluded($classAndMethod)) {
                continue;
            }


            [$class, $method] = explode('::', $classAndMethod);


            $metricsArray = array_merge($metrics, [
                'class' => $class,
                'method' => $method,
                'file' => $file
            ]);

            $metric = new CognitiveMetrics($metricsArray);

            if ($metricsCollection->contains($metric)) {
                continue;
            }

            $metricsCollection->add($metric);
        }

        return $metricsCollection;
    }

    private function isExcluded(string $classAndMethod): bool
    {
        $regexes = $this->configService->getConfig()->excludePatterns;

        foreach ($regexes as $regex) {
            if (preg_match('/' . $regex . '/', $classAndMethod)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find source files using DirectoryScanner
     *
     * @param string $path Path to the directory or file to scan
     * @param array<int, string> $exclude List of regx to exclude
     * @return iterable<mixed, SplFileInfo> An iterable of SplFileInfo objects
     * @throws CognitiveAnalysisException
     */
    private function findSourceFiles(string $path, array $exclude = []): iterable
    {
        return $this->directoryScanner->scan(
            [$path],
            array_merge(['^(?!.*\.php$).+'], $exclude)
        );
    }

    /**
     * Get the project root directory path.
     *
     * Start from the current file's directory and traverse up to find composer.json
     *
     * @return string|null The project root path or null if not found
     */
    private function getProjectRoot(): ?string
    {
        $currentDir = __DIR__;

        while ($currentDir !== dirname($currentDir)) {
            if (file_exists($currentDir . DIRECTORY_SEPARATOR . 'composer.json')) {
                return $currentDir;
            }
            $currentDir = dirname($currentDir);
        }

        return null;
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

    /**
     * Generate configuration hash for cache invalidation
     */
    private function generateConfigHash(CognitiveConfig $config): string
    {
        return md5(serialize($config->toArray()));
    }

    /**
     * Cache the analysis result for a file
     */
    /** @param array<string, mixed> $metrics */
    private function cacheResult(
        CacheItemInterface $cacheItem,
        SplFileInfo $file,
        array $metrics,
        string $configHash
    ): void {
        $cacheItem->set([
            'version' => '1.0',
            'file_path' => $file->getRealPath(),
            'file_mtime' => $file->getMTime(),
            'config_hash' => $configHash,
            'analysis_result' => $metrics,
            'ignored_items' => $this->ignoredItems,
            'cached_at' => time()
        ]);

        $this->cachePool->save($cacheItem);
    }

    public function clearCache(): void
    {
        $this->cachePool->clear();
    }

    /**
     * Normalize filename for the test environment
     *
     * This is to ensure consistent file paths in test outputs
     */
    private function normalizeFilename(SplFileInfo $file): string
    {
        $filename = $file->getRealPath();

        if (getenv('APP_ENV') !== 'test') {
            return $filename;
        }

        $projectRoot = $this->getProjectRoot();
        if ($projectRoot && str_starts_with($filename, $projectRoot)) {
            $filename = substr($filename, strlen($projectRoot) + 1);
        }

        return $filename;
    }

    /**
     * Try to get cached metrics for a file
     *
     * @return array{metrics: array<string, mixed>|null, cacheItem: CacheItemInterface|null}
     * @throws \InvalidArgumentException
     */
    private function getCachedMetrics(SplFileInfo $file, string $configHash, bool $useCache): array
    {
        if (!$useCache) {
            return ['metrics' => null, 'cacheItem' => null];
        }

        $cacheKey = $this->generateCacheKey($file, $configHash);
        $cacheItem = $this->cachePool->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            return ['metrics' => null, 'cacheItem' => $cacheItem];
        }

        $cachedData = $cacheItem->get();
        $this->ignoredItems = $cachedData['ignored_items'] ?? [];
        $this->messageBus->dispatch(new FileProcessed($file));

        return ['metrics' => $cachedData['analysis_result'], 'cacheItem' => $cacheItem];
    }

    /**
     * Process a single file and parse its metrics
     *
     * @return array<string, mixed>|null
     * @throws \InvalidArgumentException
     */
    private function processFile(
        SplFileInfo $file,
        int &$fileCount,
        ?CacheItemInterface $cacheItem,
        bool $useCache,
        string $configHash
    ): ?array {
        try {
            $metrics = $this->parser->parse(
                $this->getCodeFromFile($file)
            );

            $fileCount++;

            // Clear memory periodically to prevent memory leaks
            if ($fileCount % 50 === 0) {
                $this->parser->clearStaticCaches();
                gc_collect_cycles();
            }

            // Cache the result if caching is enabled
            if ($useCache && $cacheItem !== null) {
                $this->cacheResult($cacheItem, $file, $metrics, $configHash);
            }

            $this->messageBus->dispatch(new FileProcessed($file));

            return $metrics;
        } catch (Throwable $exception) {
            $this->messageBus->dispatch(new ParserFailed(
                $file,
                $exception
            ));

            return null;
        }
    }
}
