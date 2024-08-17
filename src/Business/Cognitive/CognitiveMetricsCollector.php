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
    public function __construct()
    {
        parent::__construct();
    }

    public function collect(string $path): CognitiveMetricsCollection
    {
        $files = $this->findSourceFiles($path);

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

        foreach ($files as $file) {
            $code = file_get_contents($file->getRealPath());

            if ($code === false) {
                throw new RuntimeException("Could not read file: {$file->getRealPath()}");
            }

            $visitor = new CognitiveMetricsVisitor();
            $this->traverser->addVisitor($visitor);

            $this->traverseAbstractSyntaxTree($code);

            $methodMetrics = $visitor->getMethodMetrics();
            $this->traverser->removeVisitor($visitor);

            $this->processMethodMetrics($methodMetrics, $metricsCollection);
        }

        return $metricsCollection;
    }

    /**
     * Process method metrics and add them to the collection
     *
     * @param array<string, mixed> $methodMetrics
     * @param CognitiveMetricsCollection $metricsCollection
     */
    private function processMethodMetrics(array $methodMetrics, CognitiveMetricsCollection $metricsCollection): void
    {
        foreach ($methodMetrics as $classAndMethod => $metrics) {
            [$class, $method] = explode('::', $classAndMethod);

            $metricsArray = array_merge($metrics, [
                'class' => $class,
                'method' => $method
            ]);

            $metric = CognitiveMetrics::fromArray($metricsArray);

            if (!$metricsCollection->contains($metric)) {
                $metricsCollection->add($metric);
            }
        }
    }
}
