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
        $files = $this->findSourceFiles($path, $config->excludeFilePatterns);

        /** @var SplFileInfo[] $clonedFiles */
        $clonedFiles = [];
        foreach ($files as $file) {
            $clonedFiles[] = $file;
        }

        $this->messageBus->dispatch(new SourceFilesFound($clonedFiles));

        return $this->findMetrics($clonedFiles);
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

        foreach ($files as $file) {
            try {
                $metrics = $this->parser->parse(
                    $this->getCodeFromFile($file)
                );

                // Store ignored items from the parser
                $this->ignoredItems = $this->parser->getIgnored();
            } catch (Throwable $exception) {
                $this->messageBus->dispatch(new ParserFailed(
                    $file,
                    $exception
                ));
                continue;
            }

            $metricsCollection = $this->processMethodMetrics(
                $metrics,
                $metricsCollection,
                $file->getRealPath()
            );

            $this->messageBus->dispatch(new FileProcessed(
                $file,
            ));
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
        return $this->directoryScanner->scan([$path], ['^(?!.*\.php$).+'] + $exclude);
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
}
