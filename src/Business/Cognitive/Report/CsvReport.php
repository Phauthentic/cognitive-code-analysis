<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

class CsvReport implements ReportGeneratorInterface, StreamableReportInterface
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

    /** @var resource|false|null */
    private $fileHandle = null;
    private bool $isStreaming = false;
    private bool $headerWritten = false;

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

        fputcsv($file, $this->header, ',', '"', '\\');

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
                ], ',', '"', '\\');
            }
        }

        fclose($file);
    }

    /**
     * @throws CognitiveAnalysisException
     */
    public function startReport(string $filename): void
    {
        $basename = dirname($filename);
        if (!is_dir($basename)) {
            throw new CognitiveAnalysisException(sprintf('Directory %s does not exist', $basename));
        }

        $this->fileHandle = fopen($filename, 'wb');
        if ($this->fileHandle === false) {
            throw new CognitiveAnalysisException(sprintf('Could not open file %s for writing', $filename));
        }

        $this->isStreaming = true;
        $this->headerWritten = false;
    }

    /**
     * @throws CognitiveAnalysisException
     */
    public function writeMetricBatch(CognitiveMetricsCollection $batch): void
    {
        if (!$this->isStreaming || $this->fileHandle === null) {
            throw new CognitiveAnalysisException('Streaming not started. Call startReport() first.');
        }

        // Type guard: fileHandle is guaranteed to be resource at this point
        assert($this->fileHandle !== false);

        // Write header only once
        if (!$this->headerWritten) {
            fputcsv($this->fileHandle, $this->header, ',', '"', '\\');
            $this->headerWritten = true;
        }

        $groupedByClass = $batch->groupBy('class');

        foreach ($groupedByClass as $methods) {
            foreach ($methods as $data) {
                fputcsv($this->fileHandle, [
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
                ], ',', '"', '\\');
            }
        }
    }

    /**
     * @throws CognitiveAnalysisException
     */
    public function finalizeReport(): void
    {
        if (!$this->isStreaming || $this->fileHandle === null) {
            throw new CognitiveAnalysisException('Streaming not started or file handle not available.');
        }

        // Type guard: fileHandle is guaranteed to be resource at this point
        assert($this->fileHandle !== false);

        fclose($this->fileHandle);

        $this->isStreaming = false;
        $this->fileHandle = null;
        $this->headerWritten = false;
    }
}
