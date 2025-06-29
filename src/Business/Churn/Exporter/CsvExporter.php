<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * CsvExporter for Churn metrics.
 */
class CsvExporter implements DataExporterInterface
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
     * @throws CognitiveAnalysisException
     */
    private function assertFileIsWritable(string $filename): void
    {
        if (file_exists($filename) && !is_writable($filename)) {
            throw new CognitiveAnalysisException(sprintf('File %s is not writable', $filename));
        }

        $dir = dirname($filename);
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new CognitiveAnalysisException(sprintf('Directory %s does not exist for file %s', $dir, $filename));
        }
    }

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
