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
    private function assertDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            throw new CognitiveAnalysisException(sprintf('Directory %s does not exist', $directory));
        }
    }

    /**
     * @throws CognitiveAnalysisException
     */
    private function assertFileIsWritable(mixed $file): void
    {
        if (!is_resource($file)) {
            throw new CognitiveAnalysisException(sprintf('Could not open file %s for writing', $file));
        }
    }

    /**
     * @param array<string, array<string, mixed>> $classes
     * @param string $filename
     * @throws CognitiveAnalysisException
     */
    public function export(array $classes, string $filename): void
    {
        $this->assertDirectoryExists($filename);

        $file = fopen($filename, 'wb');
        $this->assertFileIsWritable($file);

        /* @phpstan-ignore argument.type */
        fputcsv($file, $this->header);

        foreach ($classes as $class => $data) {
            /* @phpstan-ignore argument.type */
            fputcsv($file, [
                $class,
                $data['file'] ?? '',
                $data['score'] ?? 0,
                $data['churn'] ?? 0,
                $data['timesChanged'] ?? 0,
            ]);
        }

        /* @phpstan-ignore argument.type */
        fclose($file);
    }
}
