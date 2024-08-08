<?php

declare(strict_types=1);

namespace Phauthentic\CodeQuality\Business\Exporter;

use Phauthentic\CodeQuality\Business\MetricsCollection;
use RuntimeException;

/**
 *
 */
class CsvExporter implements DataExporterInterface
{
    /**
     * @var array<string>
     */
    private array $header = [
        'Class',
        'Method',
        'Line Count',
        'Argument Count',
        'Return Count',
        'Variable Count',
        'Property Call Count',
        'If Nesting Level',
        'Else Count',
        'Combined Cognitive Complexity'
    ];

    public function export(MetricsCollection $metrics, string $filename): void
    {
        $file = fopen($filename, 'wb');
        if ($file === false) {
            throw new RuntimeException('Could not open file for writing');
        }

        fputcsv($file, $this->header);

        $groupedByClass = $metrics->groupBy('class');

        foreach ($groupedByClass as $methods) {
            foreach ($methods as $data) {
                fputcsv($file, [
                    $data->getClass(),
                    $data->getMethod(),
                    $data->getLineCount(),
                    $data->getArgCount(),
                    $data->getReturnCount(),
                    $data->getVariableCount(),
                    $data->getPropertyCallCount(),
                    $data->getIfNestingLevel(),
                    $data->getElseCount(),
                    $data->getScore()
                ]);
            }
        }

        fclose($file);
    }
}
