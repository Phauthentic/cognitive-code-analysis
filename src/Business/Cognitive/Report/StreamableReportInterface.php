<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;

/**
 * Interface for report generators that support streaming/batch processing.
 * Allows writing metrics in batches to reduce memory usage for large datasets.
 */
interface StreamableReportInterface
{
    /**
     * Initialize the report file and write any necessary headers.
     *
     * @param string $filename The output filename
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function startReport(string $filename): void;

    /**
     * Write a batch of metrics to the report.
     *
     * @param CognitiveMetricsCollection $batch The batch of metrics to write
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function writeMetricBatch(CognitiveMetricsCollection $batch): void;

    /**
     * Finalize the report and write any necessary footers.
     *
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function finalizeReport(): void;
}
