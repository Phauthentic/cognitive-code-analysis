<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business\Cognitive;

use Phauthentic\CodeQualityMetrics\Business\AbstractMetricCollector;
use Phauthentic\CodeQualityMetrics\PhpParser\CognitiveMetricsVisitor;
use RuntimeException;
use SplFileInfo;

/**
 * CognitiveMetricsCollector class that collects cognitive metrics from source files
 */
class CognitiveMetricsCollector extends AbstractMetricCollector
{
    /**
     * Collect cognitive metrics from the given path
     *
     * @param string $path
     * @param array<string, mixed> $config
     * @return CognitiveMetricsCollection
     */
    public function collect(string $path, array $config = []): CognitiveMetricsCollection
    {
        $files = $this->findSourceFiles($path, $this->getExcludePatternsFromConfig($config));

        return $this->findMetrics($files);
    }

    /**
     * Collect metrics from the found source files
     *
     * @param iterable<SplFileInfo> $files
     * @return CognitiveMetricsCollection
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
                throw new RuntimeException("Could not read file: {$file->getRealPath()}");
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
}
