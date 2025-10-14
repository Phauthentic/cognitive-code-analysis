<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;

interface ReportGeneratorInterface
{
    public function export(CognitiveMetricsCollection $metrics, string $filename): void;
}
