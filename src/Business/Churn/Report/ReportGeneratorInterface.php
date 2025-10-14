<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetricsCollection;

interface ReportGeneratorInterface
{
    public function export(ChurnMetricsCollection $metrics, string $filename): void;
}
