<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCollection;

/**
 * CSV report generator for semantic coupling analysis.
 */
class CsvReport extends AbstractReport
{
    /**
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function export(SemanticCouplingCollection $couplings, string $filename): void
    {
        $this->assertFileIsWritable($filename);

        $csvData = [];
        
        // Add header
        $csvData[] = ['Entity1', 'Entity2', 'Score', 'Granularity', 'SharedTerms'];

        // Add data rows
        foreach ($couplings as $coupling) {
            $csvData[] = [
                $coupling->getEntity1(),
                $coupling->getEntity2(),
                number_format($coupling->getScore(), 4),
                $coupling->getGranularity(),
                implode(', ', $coupling->getSharedTerms())
            ];
        }

        // Convert to CSV format
        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= $this->arrayToCsv($row) . "\n";
        }

        $this->writeFile($filename, $csvContent);
    }

    /**
     * Convert array to CSV row.
     */
    private function arrayToCsv(array $data): string
    {
        $csv = '';
        foreach ($data as $field) {
            if ($csv !== '') {
                $csv .= ',';
            }
            
            // Escape quotes and wrap in quotes if contains comma, quote, or newline
            $escaped = str_replace('"', '""', (string)$field);
            if (strpos($escaped, ',') !== false || strpos($escaped, '"') !== false || strpos($escaped, "\n") !== false) {
                $csv .= '"' . $escaped . '"';
            } else {
                $csv .= $escaped;
            }
        }
        
        return $csv;
    }
}
