<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCollection;

/**
 * HTML report generator for semantic coupling analysis.
 */
class HtmlReport extends AbstractReport
{
    /**
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function export(SemanticCouplingCollection $couplings, string $filename): void
    {
        $this->assertFileIsWritable($filename);

        $html = $this->generateHtml($couplings);
        $this->writeFile($filename, $html);
    }

    /**
     * Generate HTML content.
     */
    private function generateHtml(SemanticCouplingCollection $couplings): string
    {
        $granularity = $couplings->count() > 0 ? $couplings->current()->getGranularity() : 'unknown';
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semantic Coupling Analysis Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background-color: #f4f4f4; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .summary-item { background-color: #e8f4fd; padding: 15px; border-radius: 5px; text-align: center; }
        .summary-value { font-size: 24px; font-weight: bold; color: #2c5aa0; }
        .summary-label { color: #666; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .score-high { background-color: #ffebee; }
        .score-medium { background-color: #fff3e0; }
        .score-low { background-color: #e8f5e8; }
        .shared-terms { font-size: 0.9em; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Semantic Coupling Analysis Report</h1>
        <p><strong>Generated:</strong> ' . $this->getCurrentTimestamp() . '</p>
        <p><strong>Granularity:</strong> ' . htmlspecialchars($granularity) . '</p>
        <p><strong>Total Couplings:</strong> ' . $couplings->count() . '</p>
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="summary-value">' . number_format($couplings->getAverageScore(), 3) . '</div>
            <div class="summary-label">Average Score</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">' . number_format($couplings->getMaxScore(), 3) . '</div>
            <div class="summary-label">Maximum Score</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">' . number_format($couplings->getMinScore(), 3) . '</div>
            <div class="summary-label">Minimum Score</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">' . number_format($couplings->getMedianScore(), 3) . '</div>
            <div class="summary-label">Median Score</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Entity 1</th>
                <th>Entity 2</th>
                <th>Coupling Score</th>
                <th>Shared Terms</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($couplings as $coupling) {
            $scoreClass = $this->getScoreClass($coupling->getScore());
            $sharedTermsHtml = !empty($coupling->getSharedTerms()) 
                ? '<div class="shared-terms">' . implode(', ', array_map('htmlspecialchars', $coupling->getSharedTerms())) . '</div>'
                : '<div class="shared-terms">None</div>';

            $html .= '
            <tr class="' . $scoreClass . '">
                <td>' . htmlspecialchars($coupling->getEntity1()) . '</td>
                <td>' . htmlspecialchars($coupling->getEntity2()) . '</td>
                <td>' . number_format($coupling->getScore(), 4) . '</td>
                <td>' . $sharedTermsHtml . '</td>
            </tr>';
        }

        $html .= '
        </tbody>
    </table>
</body>
</html>';

        return $html;
    }

    /**
     * Get CSS class based on coupling score.
     */
    private function getScoreClass(float $score): string
    {
        if ($score >= 0.7) {
            return 'score-high';
        } elseif ($score >= 0.4) {
            return 'score-medium';
        } else {
            return 'score-low';
        }
    }
}
