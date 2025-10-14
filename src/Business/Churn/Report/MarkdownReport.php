<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Reporter\MarkdownFormatterTrait;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\CoverageDataDetector;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;

/**
 * MarkdownReport for Churn metrics.
 */
class MarkdownReport extends AbstractReport
{
    use MarkdownFormatterTrait;
    use CoverageDataDetector;

    /**
     * @var array<string>
     */
    private array $header = [
        'Class',
        'Score',
        'Churn',
        'Times Changed',
    ];

    /**
     * @var array<string>
     */
    private array $headerWithCoverage = [
        'Class',
        'Score',
        'Churn',
        'Risk Churn',
        'Times Changed',
        'Coverage',
        'Risk Level',
    ];

    /**
     * @param string $filename
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function export(ChurnMetricsCollection $metrics, string $filename): void
    {
        $this->assertFileIsWritable($filename);

        $markdown = $this->generateMarkdown($metrics);

        $this->writeFile($filename, $markdown);
    }

    private function generateMarkdown(ChurnMetricsCollection $metrics): string
    {
        $hasCoverageData = $this->hasCoverageData($metrics);
        $header = $hasCoverageData ? $this->headerWithCoverage : $this->header;

        $markdown = "# Churn Metrics Report\n\n";
        $markdown .= "Generated: " . (new Datetime())->format('Y-m-d H:i:s') . "\n\n";
        $markdown .= "Total Classes: " . count($metrics) . "\n\n";

        // Create table header
        $markdown .= $this->buildMarkdownTableHeader($header) . "\n";
        $markdown .= $this->buildMarkdownTableSeparator(count($header)) . "\n";

        // Add rows
        foreach ($metrics as $metric) {
            if ($metric->getScore() == 0 || $metric->getChurn() == 0) {
                continue;
            }

            $markdown .= $this->addRow($metric, $hasCoverageData);
        }

        return $markdown;
    }

    /**
     * Add a single row to the markdown table
     *
     * @param ChurnMetrics $metric
     * @param bool $hasCoverageData
     * @return string
     */
    private function addRow(ChurnMetrics $metric, bool $hasCoverageData): string
    {
        $row = [
            $this->escapeMarkdown($metric->getClassName()),
            (string)$metric->getScore(),
            (string)round($metric->getChurn(), 3),
        ];

        if ($hasCoverageData) {
            $row[] = $metric->getRiskChurn() !== null ? (string)round($metric->getRiskChurn(), 3) : 'N/A';
        }

        $row[] = (string)$metric->getTimesChanged();

        if ($hasCoverageData) {
            $row[] = $metric->getCoverage() !== null ? sprintf('%.2f%%', $metric->getCoverage() * 100) : 'N/A';
            $row[] = $metric->getRiskLevel() ?? 'N/A';
        }

        return "| " . implode(" | ", $row) . " |\n";
    }

    /**
     * Check if the metrics collection has coverage data
     */
    private function hasCoverageData(ChurnMetricsCollection $metrics): bool
    {
        foreach ($metrics as $metric) {
            if ($metric->hasCoverageData()) {
                return true;
            }
        }
        return false;
    }
}
