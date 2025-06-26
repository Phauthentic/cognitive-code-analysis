<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;

/**
 *
 */
class ChurnCalculator
{
    public function calculate(CognitiveMetricsCollection $metricsCollection): array
    {
        $classes = [];
        $classes = $this->groupByClasses($metricsCollection, $classes);
        $classes = $this->calculateChurn($classes);

        return $this->sortClassesByChurnDescending($classes);
    }

    /**
     * @param array $classes
     * @return array
     */
    public function sortClassesByChurnDescending(array $classes): array
    {
        uasort($classes, function ($a, $b) {
            return $b['churn'] <=> $a['churn'];
        });

        return $classes;
    }

    /**
     * @param array $classes
     * @return array
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
     * @param array $classes
     * @return array
     */
    public function groupByClasses(CognitiveMetricsCollection $metricsCollection, array $classes): array
    {
        foreach ($metricsCollection as $metric) {
            if (empty($metric->getClass())){
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
}
