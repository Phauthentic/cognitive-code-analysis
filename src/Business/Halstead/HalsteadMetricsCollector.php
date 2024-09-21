<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business\Halstead;

use Phauthentic\CodeQualityMetrics\Business\AbstractMetricCollector;
use Phauthentic\CodeQualityMetrics\PhpParser\HalsteadMetricsVisitor;
use RuntimeException;
use SplFileInfo;

/**
 * HalsteadMetricsCollector class that collects Halstead metrics from source files.
 */
class HalsteadMetricsCollector extends AbstractMetricCollector
{
    /**
     * Collect Halstead metrics from the given path.
     *
     * @param string $path
     * @param array<string, mixed> $config
     * @return HalsteadMetricsCollection
     */
    public function collect(string $path, array $config = []): HalsteadMetricsCollection
    {
        $files = $this->findSourceFiles($path, $this->getExcludePatternsFromConfig($config));

        return $this->findMetrics($files);
    }

    /**
     * Collect Halstead metrics from the found source files.
     *
     * @param iterable<SplFileInfo> $files
     * @return HalsteadMetricsCollection
     */
    protected function findMetrics(iterable $files): HalsteadMetricsCollection
    {
        $metricsCollection = new HalsteadMetricsCollection();

        foreach ($files as $file) {
            $code = file_get_contents($file->getRealPath());

            if ($code === false) {
                throw new RuntimeException("Could not read file: {$file->getRealPath()}");
            }

            $halsteadMetricsVisitor = new HalsteadMetricsVisitor();
            $this->traverser->addVisitor($halsteadMetricsVisitor);

            $this->traverseAbstractSyntaxTree($code);

            $metricsData = $halsteadMetricsVisitor->getMetrics();
            $this->traverser->removeVisitor($halsteadMetricsVisitor);

            foreach ($metricsData as $class => $data) {
                $data['class'] = $class;
                $data['file'] = $file->getRealPath();
                $metrics = HalsteadMetrics::fromArray($data);

                if (!$metricsCollection->contains($metrics)) {
                    $metricsCollection->add($metrics);
                }
            }
        }

        return $metricsCollection;
    }
}
