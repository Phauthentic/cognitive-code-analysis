<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
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

    public function export(CognitiveMetricsCollection $metrics, string $filename): void
    {
        $basename = dirname($filename);
        if (!is_dir($basename)) {
            throw new RuntimeException(sprintf('Directory %s does not exist', $basename));
        }

        $file = fopen($filename, 'wb');
        if ($file === false) {
            throw new RuntimeException(sprintf('Could not open file %s for writing', $filename));
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
