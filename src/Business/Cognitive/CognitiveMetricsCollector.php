<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\FileProcessed;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\SourceFilesFound;
use Phauthentic\CognitiveCodeAnalysis\Business\DirectoryScanner;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use SplFileInfo;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * CognitiveMetricsCollector class that collects cognitive metrics from source files
 */
class CognitiveMetricsCollector
{
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
            $clonedFiles[] = clone $file;
        }

        $this->messageBus->dispatch(new SourceFilesFound($clonedFiles));

        return $this->findMetrics($clonedFiles);
    }

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
     * @throws CognitiveAnalysisException|ExceptionInterface
     */
    private function findMetrics(iterable $files): CognitiveMetricsCollection
    {
        $metricsCollection = new CognitiveMetricsCollection();

        foreach ($files as $file) {
            $metrics = $this->parser->parse(
                $this->getCodeFromFile($file)
            );

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
     * Process method metrics and add them to the collection
     *
     * @param array<string, mixed> $methodMetrics
     * @param CognitiveMetricsCollection $metricsCollection
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

            $metric = $this->getNumberChangedFromGit($file, $metric);

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
        return $this->directoryScanner->scan([$path], ['^(?!.*\.php$).+'] + $exclude); // Exclude non-PHP files
    }

    /**
     * @param string $file
     * @param CognitiveMetrics $metric
     * @return CognitiveMetrics
     */
    public function getNumberChangedFromGit(string $file, CognitiveMetrics $metric): CognitiveMetrics
    {
        $command = sprintf(
            'git -C %s rev-list --since=%s --no-merges --count HEAD -- %s',
            escapeshellarg(\dirname($file)),
            escapeshellarg('2020-01-01'), // Example date, replace with actual logic if needed
            escapeshellarg($file)
        );

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        $metric->setTimesChanged((int)$output[0]);

        return $metric;
    }
}
