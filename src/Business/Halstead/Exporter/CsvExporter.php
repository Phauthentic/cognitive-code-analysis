<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business\Halstead\Exporter;

use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollection;
use RuntimeException;

/**
 *
 */
class CsvExporter
{
    /**
     * @var string[]
     */
    protected array $header = [
        'n1', 'n2', 'N1', 'N2', 'Program Length', 'Program Vocabulary',
        'Volume', 'Difficulty', 'Effort', 'Possible Bugs', 'Class', 'File'
    ];

    private function assertTargetDirectoryExists(string $filename): void
    {
        $basename = dirname($filename);
        if (!is_dir($basename)) {
            throw new RuntimeException(sprintf('Directory %s does not exist', $basename));
        }
    }

    /**
     * @return resource
     */
    private function assertIsResource(mixed $file, string $filename): mixed
    {
        if (!is_resource($file)) {
            throw new RuntimeException(sprintf('Could not open file %s for writing', $filename));
        }

        return $file;
    }

    private function assertIsWriteablePath(string $filename): void
    {
        if (!is_writable(dirname($filename))) {
            throw new RuntimeException(sprintf('Directory %s is not writable', dirname($filename)));
        }
    }

    /**
     * Exports an array of HalsteadMetrics objects to a CSV file.
     *
     * @param HalsteadMetricsCollection $metricsCollection
     * @param string $filename
     * @return void
     */
    public function export(HalsteadMetricsCollection $metricsCollection, string $filename): void
    {
        $this->assertTargetDirectoryExists($filename);
        $this->assertIsWriteablePath($filename);

        $file = fopen($filename, 'wb');
        $file = $this->assertIsResource($file, $filename);

        fputcsv($file, $this->header);

        foreach ($metricsCollection as $metrics) {
            fputcsv($file, [
                $metrics->getN1(),
                $metrics->getN2(),
                $metrics->getTotalOperators(),
                $metrics->getTotalOperands(),
                $metrics->getProgramLength(),
                $metrics->getProgramVocabulary(),
                $metrics->getVolume(),
                $metrics->getDifficulty(),
                $metrics->getEffort(),
                $metrics->getPossibleBugs(),
                $metrics->getClass(),
                $metrics->getFile()
            ]);
        }

        fclose($file);
    }
}
