<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business\Cognitive;

use RuntimeException;

/**
 *
 */
class BaselineService
{
    /**
     * @param CognitiveMetricsCollection $metricsCollection
     * @param array<string, array<string, mixed>> $baseline
     */
    public function calculateDeltas(CognitiveMetricsCollection $metricsCollection, array $baseline): void
    {
        foreach ($baseline as $class => $data) {
            foreach ($data['methods'] as $methodName => $methodData) {
                $metrics = $metricsCollection->getClassWithMethod($class, $methodName);
                if (!$metrics) {
                    continue;
                }

                $previousMetrics = CognitiveMetrics::fromArray($methodData);
                $metrics->calculateDeltas($previousMetrics);
            }
        }
    }


    /**
     * Loads the baseline file and returns the data as an array.
     *
     * @param string $baselineFile
     * @return array<string, array<string, mixed>> $baseline
     * @throws \JsonException
     */
    public function loadBaseline(string $baselineFile): array
    {
        if (!file_exists($baselineFile)) {
            throw new RuntimeException('Baseline file does not exist.');
        }

        $baseline = file_get_contents($baselineFile);
        if ($baseline === false) {
            throw new RuntimeException('Failed to read baseline file.');
        }

        return json_decode($baseline, true, 512, JSON_THROW_ON_ERROR);
    }
}
