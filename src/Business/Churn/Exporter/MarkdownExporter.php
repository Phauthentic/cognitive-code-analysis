<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * MarkdownExporter for Churn metrics.
 */
class MarkdownExporter extends AbstractExporter
{
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
     * @param array<string, array<string, mixed>> $classes
     * @param string $filename
     * @throws CognitiveAnalysisException
     */
    public function export(array $classes, string $filename): void
    {
        $this->assertFileIsWritable($filename);

        $markdown = $this->generateMarkdown($classes);

        $this->writeFile($filename, $markdown);
    }

    /**
     * @param array<string, array<string, mixed>> $classes
     * @return string
     */
    private function generateMarkdown(array $classes): string
    {
        $hasCoverageData = $this->hasCoverageData($classes);
        $header = $hasCoverageData ? $this->headerWithCoverage : $this->header;

        $markdown = "# Churn Metrics Report\n\n";
        $markdown .= "Generated: " . (new Datetime())->format('Y-m-d H:i:s') . "\n\n";
        $markdown .= "Total Classes: " . count($classes) . "\n\n";

        // Create table header
        $markdown .= "| " . implode(" | ", $header) . " |\n";
        $markdown .= "|" . str_repeat(" --- |", count($header)) . "\n";

        // Add rows
        foreach ($classes as $className => $data) {
            if ($data['score'] == 0 || $data['churn'] == 0) {
                continue;
            }

            $markdown .= $this->addRow($className, $data, $hasCoverageData);
        }

        return $markdown;
    }

    /**
     * Add a single row to the markdown table
     *
     * @param string $className
     * @param array<string, mixed> $data
     * @param bool $hasCoverageData
     * @return string
     */
    private function addRow(string $className, array $data, bool $hasCoverageData): string
    {
        $row = [
            $this->escapeMarkdown($className),
            (string)$data['score'],
            (string)round((float)$data['churn'], 3),
        ];

        if ($hasCoverageData) {
            $row[] = $data['riskChurn'] !== null ? (string)round((float)$data['riskChurn'], 3) : 'N/A';
        }

        $row[] = (string)$data['timesChanged'];

        if ($hasCoverageData) {
            $row[] = $data['coverage'] !== null ? sprintf('%.2f%%', $data['coverage'] * 100) : 'N/A';
            $row[] = $data['riskLevel'] ?? 'N/A';
        }

        return "| " . implode(" | ", $row) . " |\n";
    }

    /**
     * Check if any class has coverage data
     *
     * @param array<string, mixed> $classes
     * @return bool
     */
    private function hasCoverageData(array $classes): bool
    {
        foreach ($classes as $data) {
            if (array_key_exists('coverage', $data) && $data['coverage'] !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Escape special markdown characters in strings
     *
     * @param string $string
     * @return string
     */
    private function escapeMarkdown(string $string): string
    {
        // Escape pipe characters which would break table formatting
        return str_replace('|', '\\|', $string);
    }
}
