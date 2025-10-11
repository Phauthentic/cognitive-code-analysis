<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * HtmlReport for Churn metrics.
 */
class HtmlReport extends AbstractReport
{
    /**
     * @var array<string>
     */
    private array $header = [
        'Score',
        'Times Changed',
        'Churn',
    ];

    /**
     * @param array<string, array<string, mixed>> $classes
     * @param string $filename
     * @throws CognitiveAnalysisException
     */
    public function export(array $classes, string $filename): void
    {
        $this->assertFileIsWritable($filename);

        $html = $this->generateHtml($classes);

        $this->writeFile($filename, $html);
    }

    private function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    private function formatNumber(float $number): string
    {
        return number_format($number, 3);
    }

    /**
     * @param array<string, array<string, mixed>> $classes
     * @return string
     * @throws CognitiveAnalysisException
     */
    private function generateHtml(array $classes): string
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Churn Metrics Report</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
        <div class="container-fluid">
            <h1 class="mb-4">Churn Metrics Report - <?php echo date('Y-m-d H:i:s') ?></h1>
            <p>
                This report contains the churn metrics for <?php echo count($classes); ?> files.
            </p>
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th class="table-secondary">Class</th>
                    <?php foreach ($this->header as $column) : ?>
                        <th class="table-secondary"><?php echo $this->escape($column); ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($classes as $class => $data) : ?>
                    <tr>
                        <td><?php echo $class; ?></td>
                        <td><?php echo $data['score'] ?? 0; ?></td>
                        <td><?php echo $data['timesChanged'] ?? 0; ?></td>
                        <td><?php echo $this->formatNumber((float)($data['churn'] ?? 0)); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
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
