<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * MarkdownExporter for Churn metrics.
 */
class MarkdownExporter implements DataExporterInterface
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
        $markdown = $this->generateMarkdown($classes);

        if (file_put_contents($filename, $markdown) === false) {
            throw new CognitiveAnalysisException("Unable to write to file: $filename");
        }
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

            $row = [
                $this->escapeMarkdown($className),
                (string)$data['score'],
                (string)round($data['churn'], 3),
            ];

            if ($hasCoverageData) {
                $row[] = $data['riskChurn'] !== null ? (string)round($data['riskChurn'], 3) : 'N/A';
            }

            $row[] = (string)$data['timesChanged'];

            if ($hasCoverageData) {
                $row[] = $data['coverage'] !== null ? sprintf('%.2f%%', $data['coverage'] * 100) : 'N/A';
                $row[] = $data['riskLevel'] ?? 'N/A';
            }

            $markdown .= "| " . implode(" | ", $row) . " |\n";
        }

        return $markdown;
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
