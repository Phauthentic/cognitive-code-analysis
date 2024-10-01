<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;

/**
 *
 */
interface DataExporterInterface
{
    public function export(CognitiveMetricsCollection $metrics, string $filename): void;
}
