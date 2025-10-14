<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Delta;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * HtmlReport class for exporting metrics as an HTML file.
 */
class HtmlReport implements ReportGeneratorInterface
{
    /**
     * @var array<string>
     */
    private array $header = [
        'Method',
        'Line Count',
        'Argument Count',
        'If Count',
        'If Nesting Level',
        'Else Count',
        'Return Count',
        'Variable Count',
        'Property Call Count',
        'Combined Cognitive Complexity'
    ];

    /**
     * Export metrics to an HTML file using Bootstrap 5.
     *
     * @param CognitiveMetricsCollection $metrics
     * @param string $filename
     * @return void
     */
    public function export(CognitiveMetricsCollection $metrics, string $filename): void
    {
        $html = $this->generateHtml($metrics);

        if (file_put_contents($filename, $html) === false) {
            throw new CognitiveAnalysisException('Could not write to file');
        }
    }

    public function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    public function formatNumber(float $number): string
    {
        return number_format($number, 3);
    }

    public function generateMetricRow(int $count, float $weight, ?Delta $delta): string
    {
        $metricRow = '<td>' . $count . ' (' . $this->formatNumber($weight) . ')';

        if ($delta !== null && !$delta->hasNotChanged()) {
            $badgeClass = $delta->hasIncreased() ? 'bg-danger' : 'bg-success';
            $deltaValue = $this->formatNumber($delta->getValue());
            $metricRow .= '<br /><span class="badge ' . $badgeClass . '">Δ ' . $deltaValue . '</span>';
        }

        return $metricRow . '</td>';
    }

    /**
     * Generate HTML content using the metrics data.
     *
     * @param CognitiveMetricsCollection $metrics
     * @return string
     */
    private function generateHtml(CognitiveMetricsCollection $metrics): string
    {
        $groupedByClass = $metrics->groupBy('class');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Cognitive Complexity Metrics Report</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
        <div class="container-fluid">
            <h1 class="mb-4">Cognitive Metrics Report - <?php echo (new Datetime())->format('Y-m-d H:i:s') ?></h1>
            <p>
                This report contains the cognitive complexity metrics for the analyzed code in <?php echo count($groupedByClass); ?> classes.
            </p>

            <?php foreach ($groupedByClass as $class => $methods) : ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th colspan="10" class="table-primary"><?php echo $this->escape((string)$class); ?></th>
                        </tr>
                        <tr>
                            <?php foreach ($this->header as $column) : ?>
                                <th class="table-secondary"><?php echo $this->escape($column); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($methods as $data) : ?>
                        <tr>
                            <td><?php echo $this->escape($data->getMethod()); ?></td>
                            <?php echo $this->generateMetricRow($data->getLineCount(), $data->getLineCountWeight(), $data->getLineCountWeightDelta()); ?>
                            <?php echo $this->generateMetricRow($data->getArgCount(), $data->getArgCountWeight(), $data->getArgCountWeightDelta()); ?>
                            <?php echo $this->generateMetricRow($data->getIfCount(), $data->getIfCountWeight(), $data->getIfCountWeightDelta()); ?>
                            <?php echo $this->generateMetricRow($data->getIfNestingLevel(), $data->getIfNestingLevelWeight(), $data->getIfNestingLevelWeightDelta()); ?>
                            <?php echo $this->generateMetricRow($data->getElseCount(), $data->getElseCountWeight(), $data->getElseCountWeightDelta()); ?>
                            <?php echo $this->generateMetricRow($data->getReturnCount(), $data->getReturnCountWeight(), $data->getReturnCountWeightDelta()); ?>
                            <?php echo $this->generateMetricRow($data->getVariableCount(), $data->getVariableCountWeight(), $data->getVariableCountWeightDelta()); ?>
                            <?php echo $this->generateMetricRow($data->getPropertyCallCount(), $data->getPropertyCallCountWeight(), $data->getPropertyCallCountWeightDelta()); ?>
                            <td><?php echo $this->formatNumber($data->getScore()); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>

        </div>
        </body>
        </html>
        <?php
        $result = ob_get_clean();

        if ($result === false) {
            throw new CognitiveAnalysisException('Could not generate HTML');
        }

        return $result;
    }
}
