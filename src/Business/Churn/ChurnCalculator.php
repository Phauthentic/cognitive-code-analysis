<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;

use function dirname;

/**
 *
 */
class ChurnCalculator
{
    /**
     * Calculate the churn for each class based on the metrics collection.
     *
     * @param CognitiveMetricsCollection $metricsCollection
     * @return array<string, array<string, mixed>>
     */
    public function calculate(CognitiveMetricsCollection $metricsCollection): array
    {
        foreach ($metricsCollection as $metric) {
            $this->getNumberChangedFromGit($metric);
        }

        $classes = [];
        $classes = $this->groupByClasses($metricsCollection, $classes);
        $classes = $this->calculateChurn($classes);

        return $this->sortClassesByChurnDescending($classes);
    }

    /**
     * @param array<string, array<string, mixed>> $classes
     * @return array<string, array<string, mixed>>
     */
    public function sortClassesByChurnDescending(array $classes): array
    {
        uasort($classes, function ($classA, $classB) {
            return $classB['churn'] <=> $classA['churn'];
        });

        return $classes;
    }

    /**
     * @param array<string, array<string, mixed>> $classes
     * @return array<string, array<string, mixed>>
     */
    public function calculateChurn(array $classes): array
    {
        foreach ($classes as $className => $data) {
            $classes[$className]['churn'] = $data['timesChanged'] * $data['score'];
        }

        return $classes;
    }

    /**
     * @param CognitiveMetricsCollection $metricsCollection
     * @param array<string, array<string, mixed>> $classes
     * @return array<string, array<string, mixed>>
     */
    public function groupByClasses(CognitiveMetricsCollection $metricsCollection, array $classes): array
    {
        foreach ($metricsCollection as $metric) {
            if (empty($metric->getClass())) {
                continue;
            }

            if (!isset($classes[$metric->getClass()])) {
                $classes[$metric->getClass()] = [
                    'timesChanged' => 0,
                    'score' => 0,
                ];
            }

            $classes[$metric->getClass()]['timesChanged'] = $metric->getTimesChanged();
            $classes[$metric->getClass()]['score'] += $metric->getScore();
        }
        return $classes;
    }

    /**
     * @param CognitiveMetrics $metric
     * @return CognitiveMetrics
     */
    public function getNumberChangedFromGit(CognitiveMetrics $metric): CognitiveMetrics
    {
        $command = sprintf(
            'git -C %s rev-list --since=%s --no-merges --count HEAD -- %s',
            escapeshellarg(dirname($metric->getFileName())),
            escapeshellarg('1900-01-01'),
            escapeshellarg($metric->getFileName())
        );

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        $metric->setTimesChanged((int)$output[0]);

        return $metric;
    }
}
