<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business\Cognitive\Exporter;

use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollection;

/**
 *
 */
class JsonExporter implements DataExporterInterface
{
    public function export(CognitiveMetricsCollection $metricsCollection, string $filename): void
    {
        $jsonData = [];

        $groupedByClass = $metricsCollection->groupBy('class');

        foreach ($groupedByClass as $class => $methods) {
            foreach ($methods as $metrics) {
                $jsonData[] = [
                    'class' => $metrics->getClass(),
                    'methods' => [
                        $metrics->getMethod() => [
                            'name' => $metrics->getMethod(),
                            'line_count' => $metrics->getLineCount(),
                            'arg_count' => $metrics->getArgCount(),
                            'return_count' => $metrics->getReturnCount(),
                            'variable_count' => $metrics->getVariableCount(),
                            'property_call_count' => $metrics->getPropertyCallCount(),
                            'if_nesting_level' => $metrics->getIfNestingLevel(),
                            'else_count' => $metrics->getElseCount(),
                            'score' => $metrics->getScore()
                        ]
                    ]
                ];

                $metrics->setScore(
                    $metrics->getPropertyCallCount() + $metrics->getVariableCount() + $metrics->getArgCount()
                );
            }
        }

        $jsonData = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        if (file_put_contents($filename, $jsonData) === false) {
            throw new \RuntimeException("Unable to write to file: $filename");
        }
    }
}
