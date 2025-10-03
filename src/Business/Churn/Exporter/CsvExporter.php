<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * CsvExporter for Churn metrics.
 */
class CsvExporter extends AbstractExporter
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
     * @param array<string, array<string, mixed>> $classes
     * @param string $filename
     * @throws CognitiveAnalysisException
     */
    public function export(array $classes, string $filename): void
    {
        $this->assertFileIsWritable($filename);

        $file = fopen($filename, 'wb');

        /* @phpstan-ignore argument.type */
        fputcsv($file, $this->header, ',', '"', '\\');

        foreach ($classes as $class => $data) {
            /* @phpstan-ignore argument.type */
            fputcsv($file, [
                $class,
                $data['file'] ?? '',
                $data['score'] ?? 0,
                $data['churn'] ?? 0,
                $data['timesChanged'] ?? 0,
            ], ',', '"', '\\');
        }

        /* @phpstan-ignore argument.type */
        fclose($file);
    }
}
