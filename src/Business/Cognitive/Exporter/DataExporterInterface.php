<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business\Cognitive\Exporter;

use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollection;

/**
 *
 */
interface DataExporterInterface
{
    public function export(CognitiveMetricsCollection $metrics, string $filename): void;
}
