<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

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
        'Line Count Weight',
        'Line Count Weight Delta',
        'Argument Count',
        'Argument Count Weight',
        'Argument Count Weight Delta',
        'Return Count',
        'Return Count Weight',
        'Return Count Weight Delta',
        'Variable Count',
        'Variable Count Weight',
        'Variable Count Weight Delta',
        'Property Call Count',
        'Property Call Count Weight',
        'Property Call Count Weight Delta',
        'If Nesting Level',
        'If Nesting Level Weight',
        'If Nesting Level Weight Delta',
        'Else Count',
        'Combined Cognitive Complexity'
    ];

    /**
     * @throws CognitiveAnalysisException
     */
    public function export(CognitiveMetricsCollection $metrics, string $filename): void
    {
        $basename = dirname($filename);
        if (!is_dir($basename)) {
            throw new CognitiveAnalysisException(sprintf('Directory %s does not exist', $basename));
        }

        $file = fopen($filename, 'wb');
        if ($file === false) {
            throw new CognitiveAnalysisException(sprintf('Could not open file %s for writing', $filename));
        }

        fputcsv($file, $this->header);

        $groupedByClass = $metrics->groupBy('class');

        foreach ($groupedByClass as $methods) {
            foreach ($methods as $data) {
                fputcsv($file, [
                    $data->getClass(),
                    $data->getMethod(),

                    $data->getLineCount(),
                    $data->getLineCountWeight(),
                    (string)$data->getLineCountWeightDelta(),

                    $data->getArgCount(),
                    $data->getArgCountWeight(),
                    (string)$data->getArgCountWeightDelta(),

                    $data->getReturnCount(),
                    $data->getReturnCountWeight(),
                    (string)$data->getReturnCountWeightDelta(),

                    $data->getVariableCount(),
                    $data->getVariableCountWeight(),
                    (string)$data->getVariableCountWeightDelta(),

                    $data->getPropertyCallCount(),
                    $data->getPropertyCallCountWeight(),
                    (string)$data->getPropertyCallCountWeightDelta(),

                    $data->getIfNestingLevel(),
                    $data->getIfNestingLevelWeight(),
                    (string)$data->getIfNestingLevelWeightDelta(),

                    $data->getElseCount(),
                    $data->getElseCountWeight(),
                    (string)$data->getElseCountWeightDelta(),

                    $data->getScore()
                ]);
            }
        }

        fclose($file);
    }
}
