<?php

declare(strict_types=1);

namespace Phauthentic\CodeQuality\Business;

use Phauthentic\CodeQuality\Business\Exporter\CsvExporter;
use Phauthentic\CodeQuality\Business\Exporter\JsonExporter;

/**
 *
 */
class MetricsFacade
{
    private MetricsCollector $collector;
    private ScoreCalculator $scoreCalculator;

    public function __construct()
    {
        $this->collector = new MetricsCollector();
        $this->scoreCalculator = new ScoreCalculator();
    }

    public function getMetrics(string $path): MetricsCollection
    {
        $metricsCollection = $this->collector->collect($path);

        foreach ($metricsCollection as $metric) {
            $this->scoreCalculator->calculate($metric);
        }

        return $metricsCollection;
    }

    public function metricsCollectionToCsv(MetricsCollection $metricsCollection, string $path): void
    {
        $exporter = new CsvExporter();
        $exporter->export($metricsCollection, $path);
    }

    public function metricsCollectionToJson(MetricsCollection $metricsCollection, string $path): void
    {
        $exporter = new JsonExporter();
        $exporter->export($metricsCollection, $path);
    }
}
