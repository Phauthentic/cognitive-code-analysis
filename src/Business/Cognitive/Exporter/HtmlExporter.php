<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use RuntimeException;

/**
 * HtmlExporter class for exporting metrics as an HTML file.
 */
class HtmlExporter implements DataExporterInterface
{
    /**
     * @var array<string>
     */
    private array $header = [
        'Class',
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
            throw new RuntimeException('Could not write to file');
        }
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
                <h1 class="mb-4">Cognitive Metrics Report</h1>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <?php foreach ($this->header as $column) : ?>
                                <th><?php echo htmlspecialchars($column, ENT_QUOTES, 'UTF-8'); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupedByClass as $methods) : ?>
                            <?php foreach ($methods as $data) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data->getClass(), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($data->getMethod(), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)$data->getLineCount(), ENT_QUOTES, 'UTF-8') . ' (' . number_format($data->getLineCountWeight(), 3) . ')'; ?></td>
                                    <td><?php echo htmlspecialchars((string)$data->getArgCount(), ENT_QUOTES, 'UTF-8') . ' (' . number_format($data->getArgCountWeight(), 3) . ')'; ?></td>
                                    <td><?php echo htmlspecialchars((string)$data->getIfCount(), ENT_QUOTES, 'UTF-8') . ' (' . number_format($data->getIfCountWeight(), 3) . ')'; ?></td>
                                    <td><?php echo htmlspecialchars((string)$data->getIfNestingLevel(), ENT_QUOTES, 'UTF-8') . ' (' . number_format($data->getIfNestingLevelWeight(), 3) . ')'; ?></td>
                                    <td><?php echo htmlspecialchars((string)$data->getElseCount(), ENT_QUOTES, 'UTF-8') . ' (' . number_format($data->getElseCountWeight(), 3) . ')'; ?></td>
                                    <td><?php echo htmlspecialchars((string)$data->getReturnCount(), ENT_QUOTES, 'UTF-8') . ' (' . number_format($data->getReturnCountWeight(), 3) . ')'; ?></td>
                                    <td><?php echo htmlspecialchars((string)$data->getVariableCount(), ENT_QUOTES, 'UTF-8') . ' (' . number_format($data->getVariableCountWeight(), 3) . ')'; ?></td>
                                    <td><?php echo htmlspecialchars((string)$data->getPropertyCallCount(), ENT_QUOTES, 'UTF-8') . ' (' . number_format($data->getPropertyCallCountWeight(), 3) . ')'; ?></td>
                                    <td><?php echo number_format($data->getScore(), 3); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </body>
        </html>
        <?php
        $result = ob_get_clean();

        if ($result === false) {
            throw new RuntimeException('Could not generate HTML');
        }

        return $result;
    }
}
