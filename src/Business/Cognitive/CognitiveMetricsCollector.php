<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\FileProcessed;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\ParserFailed;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\SourceFilesFound;
use Phauthentic\CognitiveCodeAnalysis\Business\DirectoryScanner;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use SplFileInfo;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;
use Psr\Cache\CacheItemPoolInterface;

/**
 * CognitiveMetricsCollector class that collects cognitive metrics from source files
 */
class CognitiveMetricsCollector
{
    /**
     * @var array<string, array<string, string>>|null Cached ignored items from the last parsing operation
     */
    private ?array $ignoredItems = null;

    public function __construct(
        protected readonly Parser $parser,
        protected readonly DirectoryScanner $directoryScanner,
        protected readonly ConfigService $configService,
        protected readonly MessageBusInterface $messageBus,
        protected readonly ?CacheItemPoolInterface $cachePool = null,
    ) {
    }

    /**
     * Collect cognitive metrics from the given path
     *
     * @param string $path
     * @param CognitiveConfig $config
     * @return CognitiveMetricsCollection
     * @throws CognitiveAnalysisException|ExceptionInterface
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
     * @throws CognitiveAnalysisException|ExceptionInterface
     */
    public function collectFromPaths(array $paths, CognitiveConfig $config): CognitiveMetricsCollection
    {
        $allFiles = [];

        foreach ($paths as $path) {
            $files = $this->findSourceFiles($path, $config->excludeFilePatterns);
            $allFiles = array_merge($allFiles, iterator_to_array($files));
        }

        $this->messageBus->dispatch(new SourceFilesFound($allFiles));

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
     * @throws ExceptionInterface
     */
    private function findMetrics(iterable $files): CognitiveMetricsCollection
    {
        $metricsCollection = new CognitiveMetricsCollection();
        $fileCount = 0;
        $config = $this->configService->getConfig();
        $configHash = $this->generateConfigHash($config);

        foreach ($files as $file) {
            $metrics = null;
            $useCache = $this->cachePool !== null && $config->cache?->enabled === true;
            $cacheItem = null;


            if ($useCache) {
                $cacheKey = $this->generateCacheKey($file, $configHash);
                $cacheItem = $this->cachePool->getItem($cacheKey);
                
                if ($cacheItem->isHit()) {
                    // Use cached result
                    $cachedData = $cacheItem->get();
                    $metrics = $cachedData['analysis_result'];
                    $this->ignoredItems = $cachedData['ignored_items'];
                    
                    $this->messageBus->dispatch(new FileProcessed($file));
                }
            }

            if ($metrics === null) {
                // Parse file and cache result
                try {
                    $metrics = $this->parser->parse(
                        $this->getCodeFromFile($file)
                    );

                    // Store ignored items from the parser
                    $this->ignoredItems = $this->parser->getIgnored();

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
                } catch (Throwable $exception) {
                    $this->messageBus->dispatch(new ParserFailed(
                        $file,
                        $exception
                    ));
                    continue;
                }
                
                $this->messageBus->dispatch(new FileProcessed($file));
            }

            $filename = $file->getRealPath();

            if (getenv('APP_ENV') === 'test') {
                $projectRoot = $this->getProjectRoot();
                if ($projectRoot && str_starts_with($filename, $projectRoot)) {
                    $filename = substr($filename, strlen($projectRoot) + 1);
                }
            }

            $metricsCollection = $this->processMethodMetrics(
                $metrics,
                $metricsCollection,
                $filename
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

            if (!$metricsCollection->contains($metric)) {
                $metricsCollection->add($metric);
            }
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
     */
    private function findSourceFiles(string $path, array $exclude = []): iterable
    {
        return $this->directoryScanner->scan(
            [$path],
            array_merge(['^(?!.*\.php$).+'], $exclude)
        );
    }

    /**
     * Get all ignored classes and methods from the last parsing operation.
     *
     * @return array<string, array<string, string>> Array with 'classes' and 'methods' keys
     */
    public function getIgnored(): array
    {
        return $this->ignoredItems ?? ['classes' => [], 'methods' => []];
    }

    /**
     * Get ignored classes from the last parsing operation.
     *
     * @return array<string, string> Array of ignored class FQCNs
     */
    public function getIgnoredClasses(): array
    {
        return $this->ignoredItems['classes'] ?? [];
    }

    /**
     * Get ignored methods from the last parsing operation.
     *
     * @return array<string, string> Array of ignored method keys (ClassName::methodName)
     */
    public function getIgnoredMethods(): array
    {
        return $this->ignoredItems['methods'] ?? [];
    }

    /**
     * Get the project root directory path.
     *
     * @return string|null The project root path or null if not found
     */
    private function getProjectRoot(): ?string
    {
        // Start from the current file's directory and traverse up to find composer.json
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
     * Generate cache key for a file based on path, modification time, and config hash
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
        return md5(serialize($this->getConfigAsArray($config)));
    }

    /**
     * Cache the analysis result for a file
     */
    private function cacheResult($cacheItem, SplFileInfo $file, array $metrics, string $configHash): void
    {
        if (!$this->cachePool) {
            return;
        }

        $data = [
            'version' => '1.0',
            'file_path' => $file->getRealPath(),
            'file_mtime' => $file->getMTime(),
            'config_hash' => $configHash,
            'analysis_result' => $metrics,
            'ignored_items' => $this->ignoredItems,
            'cached_at' => time()
        ];
        
        $cacheItem->set($data);
        $this->cachePool->save($cacheItem);
    }

    /**
     * Get configuration as array for serialization
     */
    private function getConfigAsArray(CognitiveConfig $config): array
    {
        return [
            'excludeFilePatterns' => $config->excludeFilePatterns,
            'excludePatterns' => $config->excludePatterns,
            'scoreThreshold' => $config->scoreThreshold,
            'showOnlyMethodsExceedingThreshold' => $config->showOnlyMethodsExceedingThreshold,
            'showHalsteadComplexity' => $config->showHalsteadComplexity,
            'showCyclomaticComplexity' => $config->showCyclomaticComplexity,
            'groupByClass' => $config->groupByClass,
            'showDetailedCognitiveMetrics' => $config->showDetailedCognitiveMetrics,
            'cache' => $config->cache ? [
                'enabled' => $config->cache->enabled,
                'directory' => $config->cache->directory,
                'compression' => $config->cache->compression,
            ] : null,
        ];
    }
}
