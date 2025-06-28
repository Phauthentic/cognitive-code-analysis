<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter;

use JsonException;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 *
 */
class JsonExporter implements DataExporterInterface
{
    /**
     * @throws JsonException|CognitiveAnalysisException
     */
    public function export(CognitiveMetricsCollection $metricsCollection, string $filename): void
    {
        $jsonData = [];

        $groupedByClass = $metricsCollection->groupBy('class');

        foreach ($groupedByClass as $class => $methods) {
            foreach ($methods as $metrics) {
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
                    'score' => $metrics->getScore()
                ];
            }
        }

        $jsonData = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        if (file_put_contents($filename, $jsonData) === false) {
            throw new CognitiveAnalysisException("Unable to write to file: $filename");
        }
    }
}
