<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business\Halstead\Exporter;

use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollection;

/**
 *
 */
class JsonExporter
{
    /**
     * Exports an array of HalsteadMetrics objects to a CSV file.
     *
     * @param HalsteadMetricsCollection $metricsCollection
     * @param string $filename
     * @return void
     */
    public function export(HalsteadMetricsCollection $metricsCollection, string $filename): void
    {
        $json = json_encode($metricsCollection, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        file_put_contents($filename, $json);
    }
}
