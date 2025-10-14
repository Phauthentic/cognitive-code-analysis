<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetricsCollection;

/**
 * CsvReport for Churn metrics.
 */
class CsvReport extends AbstractReport
{
    /**
     * @var array<string>
     */
    private array $header = [
        'Class',
        'File',
        'Score',
        'Churn',
        'Times Changed',
    ];

    /**
     * @param string $filename
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function export(ChurnMetricsCollection $metrics, string $filename): void
    {
        $this->assertFileIsWritable($filename);

        $file = fopen($filename, 'wb');

        /* @phpstan-ignore argument.type */
        fputcsv($file, $this->header, ',', '"', '\\');

        foreach ($metrics as $metric) {
            /* @phpstan-ignore argument.type */
            fputcsv($file, [
                $metric->getClassName(),
                $metric->getFile(),
                $metric->getScore(),
                $metric->getChurn(),
                $metric->getTimesChanged(),
            ], ',', '"', '\\');
        }

        /* @phpstan-ignore argument.type */
        fclose($file);
    }
}
