<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business\Cognitive;

use Phauthentic\CodeQualityMetrics\Business\DirectoryScanner;
use Phauthentic\CodeQualityMetrics\CognitiveAnalysisException;
use Phauthentic\CodeQualityMetrics\Config\ConfigService;
use Phauthentic\CodeQualityMetrics\PhpParser\CognitiveMetricsVisitor;
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
     * @param array<string, mixed> $config
     * @return CognitiveMetricsCollection
     * @throws CognitiveAnalysisException
     */
    public function collect(string $path, array $config = []): CognitiveMetricsCollection
    {
        $files = $this->findSourceFiles($path);

        return $this->findMetrics($files);
    }

    /**
     * Collect metrics from the found source files
     *
     * @param iterable<SplFileInfo> $files
     * @return CognitiveMetricsCollection
     * @throws CognitiveAnalysisException
     */
    protected function findMetrics(iterable $files): CognitiveMetricsCollection
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

            $code = file_get_contents($file->getRealPath());

            if ($code === false) {
                throw new CognitiveAnalysisException("Could not read file: {$file->getRealPath()}");
            }

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

    public function isExcluded(string $classAndMethod): bool
    {
        $regexes = $this->configService->getConfig()['cognitive']['excludePatterns'];

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
     * @return iterable<mixed, SplFileInfo> An iterable of SplFileInfo objects
     */
    protected function findSourceFiles(string $path): iterable
    {
        return $this->directoryScanner->scan(
            [$path],
            ['^(?!.*\.php$).+'] + $this->configService->getConfig()['cognitive']['excludeFilePatterns']
        );
    }

    /**
     * @throws CognitiveAnalysisException
     */
    protected function traverseAbstractSyntaxTree(string $code): void
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
