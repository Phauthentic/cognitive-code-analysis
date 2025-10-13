<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

class ClassMetricsStatistics
{
    /**
     * Calculate the average score across all metrics in a collection.
     * 
     * @param CognitiveMetricsCollection $collection
     * @return float The average score, or 0.0 if the collection is empty
     */
    public function calculateAverageScore(CognitiveMetricsCollection $collection): float
    {
        if ($collection->count() === 0) {
            return 0.0;
        }

        $totalScore = 0.0;
        foreach ($collection as $metric) {
            $totalScore += $metric->getScore();
        }

        return round($totalScore / $collection->count(), 3);
    }

    /**
     * Count the number of methods that exceed the given threshold.
     * 
     * @param CognitiveMetricsCollection $collection
     * @param float $threshold
     * @return int Number of methods exceeding the threshold
     */
    public function countMethodsExceedingThreshold(CognitiveMetricsCollection $collection, float $threshold): int
    {
        $count = 0;
        foreach ($collection as $metric) {
            if ($metric->getScore() > $threshold) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Calculate the percentage of methods exceeding the threshold.
     * 
     * @param CognitiveMetricsCollection $collection
     * @param float $threshold
     * @return float Percentage (0-100), or 0.0 if a collection is empty
     */
    public function calculatePercentageExceedingThreshold(CognitiveMetricsCollection $collection, float $threshold): float
    {
        if ($collection->count() === 0) {
            return 0.0;
        }

        $exceedingCount = $this->countMethodsExceedingThreshold($collection, $threshold);
        return round(($exceedingCount / $collection->count()) * 100, 1);
    }

    /**
     * Check if a class has any methods exceeding the threshold.
     * 
     * @param CognitiveMetricsCollection $collection
     * @param float $threshold
     * @return bool True if at least one method exceeds the threshold
     */
    public function hasMethodsExceedingThreshold(CognitiveMetricsCollection $collection, float $threshold): bool
    {
        foreach ($collection as $metric) {
            if ($metric->getScore() > $threshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate overall statistics for multiple class collections.
     * 
     * @param array<string, CognitiveMetricsCollection> $groupedCollections
     * @param float $threshold
     * @return array{totalClasses: int, classesExceedingThreshold: int, percentageExceedingThreshold: float}
     */
    public function calculateOverallStatistics(array $groupedCollections, float $threshold): array
    {
        $totalClasses = count($groupedCollections);
        $classesExceedingThreshold = 0;

        foreach ($groupedCollections as $collection) {
            if ($this->hasMethodsExceedingThreshold($collection, $threshold)) {
                $classesExceedingThreshold++;
            }
        }

        $percentageExceedingThreshold = $totalClasses > 0 
            ? round(($classesExceedingThreshold / $totalClasses) * 100, 1)
            : 0.0;

        return [
            'totalClasses' => $totalClasses,
            'classesExceedingThreshold' => $classesExceedingThreshold,
            'percentageExceedingThreshold' => $percentageExceedingThreshold
        ];
    }
}
