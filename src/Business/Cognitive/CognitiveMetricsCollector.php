<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\DirectoryScanner;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\CognitiveMetricsVisitor;
use PhpParser\Error;
use PhpParser\NodeTraverserInterface;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use SplFileInfo;

/**
 * CognitiveMetricsCollector class that collects cognitive metrics from source files
 */
class CognitiveMetricsCollector
{
    protected Parser $parser;

    /**
     * @param array<int, FindMetricsPluginInterface> $findMetricsPlugins
     */
    public function __construct(
        protected readonly ParserFactory $parserFactory,
        protected readonly NodeTraverserInterface $traverser,
        protected readonly DirectoryScanner $directoryScanner,
        protected readonly ConfigService $configService,
        protected readonly array $findMetricsPlugins = []
    ) {
        $this->parser = $parserFactory->createForHostVersion();
    }

    /**
     * Collect cognitive metrics from the given path
     *
     * @param string $path
     * @param CognitiveConfig $config
     * @return CognitiveMetricsCollection
     * @throws CognitiveAnalysisException
     */
    public function collect(string $path, CognitiveConfig $config): CognitiveMetricsCollection
    {
        $files = $this->findSourceFiles($path, $config->excludeFilePatterns);

        return $this->findMetrics($files);
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
     * @throws CognitiveAnalysisException
     */
    private function findMetrics(iterable $files): CognitiveMetricsCollection
    {
        $metricsCollection = new CognitiveMetricsCollection();
        $visitor = new CognitiveMetricsVisitor();

        foreach ($this->findMetricsPlugins as $plugin) {
            $plugin->beforeIteration($files);
        }

        foreach ($files as $file) {
            foreach ($this->findMetricsPlugins as $plugin) {
                $plugin->beforeFindMetrics($file);
            }

            $code = $this->getCodeFromFile($file);

            $this->traverser->addVisitor($visitor);
            $this->traverseAbstractSyntaxTree($code);

            $methodMetrics = $visitor->getMethodMetrics();
            $this->traverser->removeVisitor($visitor);

            $this->processMethodMetrics($methodMetrics, $metricsCollection);

            foreach ($this->findMetricsPlugins as $plugin) {
                $plugin->afterFindMetrics($file);
            }
        }

        foreach ($this->findMetricsPlugins as $plugin) {
            $plugin->afterIteration($metricsCollection);
        }

        return $metricsCollection;
    }

    /**
     * Process method metrics and add them to the collection
     *
     * @param array<string, mixed> $methodMetrics
     * @param CognitiveMetricsCollection $metricsCollection
     */
    private function processMethodMetrics(
        array $methodMetrics,
        CognitiveMetricsCollection $metricsCollection
    ): void {
        foreach ($methodMetrics as $classAndMethod => $metrics) {
            if ($this->isExcluded($classAndMethod)) {
                continue;
            }

            [$class, $method] = explode('::', $classAndMethod);

            $metricsArray = array_merge($metrics, [
                'class' => $class,
                'method' => $method
            ]);

            $metric = new CognitiveMetrics($metricsArray);

            if (!$metricsCollection->contains($metric)) {
                $metricsCollection->add($metric);
            }
        }
    }

    private function isExcluded(string $classAndMethod): bool
    {
        $regexes = $this->configService->getConfig()->excludePatterns;

        foreach ($regexes as $regex) {
            if (preg_match('/' . $regex . '/', $classAndMethod, $matches)) {
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
     * @throws CognitiveAnalysisException
     */
    private function traverseAbstractSyntaxTree(string $code): void
    {
        try {
            $ast = $this->parser->parse($code);
        } catch (Error $e) {
            throw new CognitiveAnalysisException("Parse error: {$e->getMessage()}", 0, $e);
        }

        if ($ast === null) {
            throw new CognitiveAnalysisException("Could not parse the code.");
        }

        $this->traverser->traverse($ast);
    }
}
