<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

class JsonReport implements ReportGeneratorInterface, StreamableReportInterface
{
    private ?string $filename = null;
    /** @var array<string, array{methods: array<string, array<string, int|float|string>>}> */
    private array $jsonData = [];
    private bool $isStreaming = false;

    /**
     * Writes the report class by class, so neither the whole report array nor the whole JSON string
     * is held in memory. The output is the same as json_encode() of the whole array with JSON_PRETTY_PRINT.
     *
     * @throws \JsonException|\Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function export(CognitiveMetricsCollection $metricsCollection, string $filename): void
    {
        $handle = fopen($filename, 'wb');
        if ($handle === false) {
            throw new CognitiveAnalysisException("Unable to write to file: $filename");
        }

        try {
            $this->writeClasses($handle, $metricsCollection, $filename);
        } catch (\Throwable $exception) {
            // Do not leave a partial report behind
            fclose($handle);
            unlink($filename);
            throw $exception;
        }

        fclose($handle);
    }

    /**
     * @param resource $handle
     * @throws \JsonException|CognitiveAnalysisException
     */
    private function writeClasses($handle, CognitiveMetricsCollection $metricsCollection, string $filename): void
    {
        $separator = "{\n";

        foreach ($metricsCollection->groupBy('class') as $class => $methods) {
            $classData = [];
            foreach ($methods as $metrics) {
                $this->addClassMethodMetrics($classData, (string) $class, $metrics);
            }

            $encodedClass = json_encode($classData[(string) $class], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            $this->write($handle, $separator . '    '
                . json_encode((string) $class, JSON_THROW_ON_ERROR) . ': '
                . str_replace("\n", "\n    ", $encodedClass), $filename);
            $separator = ",\n";
        }

        // json_encode() writes an empty array as a list
        $this->write($handle, $separator === "{\n" ? '[]' : "\n}", $filename);
    }

    /**
     * @param resource $handle
     * @throws CognitiveAnalysisException
     */
    private function write($handle, string $data, string $filename): void
    {
        if (fwrite($handle, $data) === false) {
            throw new CognitiveAnalysisException("Unable to write to file: $filename");
        }
    }

    public function startReport(string $filename): void
    {
        $this->filename = $filename;
        $this->jsonData = [];
        $this->isStreaming = true;
    }

    /**
     * @throws CognitiveAnalysisException
     */
    public function writeMetricBatch(CognitiveMetricsCollection $batch): void
    {
        if (!$this->isStreaming) {
            throw new CognitiveAnalysisException('Streaming not started. Call startReport() first.');
        }

        $groupedByClass = $batch->groupBy('class');

        foreach ($groupedByClass as $class => $methods) {
            foreach ($methods as $metrics) {
                $this->addClassMethodMetrics($this->jsonData, (string) $class, $metrics);
            }
        }
    }

    /**
     * @param array<string, array{methods: array<string, array<string, int|float|string>>}> $jsonData
     */
    private function addClassMethodMetrics(array &$jsonData, string $class, CognitiveMetrics $metrics): void
    {
        if (!isset($jsonData[$class])) {
            $jsonData[$class] = ['methods' => []];
        }

        $jsonData[$class]['methods'][$metrics->getMethod()] = [
            'class' => $metrics->getClass(),
            'method' => $metrics->getMethod(),
            'lineCount' => $metrics->getLineCount(),
            'lineCountWeight' => $metrics->getLineCountWeight(),
            'argCount' => $metrics->getArgCount(),
            'argCountWeight' => $metrics->getArgCountWeight(),
            'returnCount' => $metrics->getReturnCount(),
            'returnCountWeight' => $metrics->getReturnCountWeight(),
            'variableCount' => $metrics->getVariableCount(),
            'variableCountWeight' => $metrics->getVariableCountWeight(),
            'propertyCallCount' => $metrics->getPropertyCallCount(),
            'propertyCallCountWeight' => $metrics->getPropertyCallCountWeight(),
            'ifCount' => $metrics->getIfCount(),
            'ifCountWeight' => $metrics->getIfCountWeight(),
            'ifNestingLevel' => $metrics->getIfNestingLevel(),
            'ifNestingLevelWeight' => $metrics->getIfNestingLevelWeight(),
            'elseCount' => $metrics->getElseCount(),
            'elseCountWeight' => $metrics->getElseCountWeight(),
            'score' => $metrics->getScore(),
        ];
    }

    /**
     * @throws \JsonException|CognitiveAnalysisException
     */
    public function finalizeReport(): void
    {
        if (!$this->isStreaming || $this->filename === null) {
            throw new CognitiveAnalysisException('Streaming not started or filename not set.');
        }

        $jsonData = json_encode($this->jsonData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        if (file_put_contents($this->filename, $jsonData) === false) {
            throw new CognitiveAnalysisException("Unable to write to file: {$this->filename}");
        }

        $this->isStreaming = false;
        $this->filename = null;
        $this->jsonData = [];
    }
}
