<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn;

use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoverageReportReaderInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;

class ChurnCalculator
{
    /**
     * Calculate the churn for each class based on the metrics collection.
     *
     * @param CognitiveMetricsCollection $metricsCollection
     * @param CoverageReportReaderInterface|null $coverageReader
     * @return ChurnMetricsCollection
     */
    public function calculate(
        CognitiveMetricsCollection $metricsCollection,
        ?CoverageReportReaderInterface $coverageReader = null
    ): ChurnMetricsCollection {
        $collection = $this->groupByClasses($metricsCollection);
        $collection = $this->calculateChurn($collection, $coverageReader);

        return $collection->sortByChurnDescending();
    }

    public function sortClassesByChurnDescending(ChurnMetricsCollection $collection): ChurnMetricsCollection
    {
        return $collection->sortByChurnDescending();
    }

    public function calculateChurn(
        ChurnMetricsCollection $collection,
        ?CoverageReportReaderInterface $coverageReader = null
    ): ChurnMetricsCollection {
        $newCollection = new ChurnMetricsCollection();

        foreach ($collection as $metric) {
            // Calculate standard churn
            $churn = $metric->getTimesChanged() * $metric->getScore();
            $metric->setChurn($churn);

            // Add coverage information if available
            $coverage = null;
            $riskChurn = null;
            $riskLevel = null;

            if ($coverageReader !== null) {
                $coverage = $this->getCoverageForClass($metric->getClassName(), $coverageReader);
                $riskChurn = $metric->getTimesChanged() * $metric->getScore() * (1 - $coverage);
                $riskLevel = $this->calculateRiskLevel($metric->getChurn(), $coverage);
            }

            $metric->setCoverage($coverage);
            $metric->setRiskChurn($riskChurn);
            $metric->setRiskLevel($riskLevel);

            $newCollection->add($metric);
        }

        return $newCollection;
    }

    public function groupByClasses(CognitiveMetricsCollection $metricsCollection): ChurnMetricsCollection
    {
        $collection = new ChurnMetricsCollection();
        $classData = [];

        foreach ($metricsCollection as $metric) {
            if (empty($metric->getClass())) {
                continue;
            }

            $className = $metric->getClass();
            if (!isset($classData[$className])) {
                $classData[$className] = [
                    'timesChanged' => 0,
                    'score' => 0,
                    'file' => $metric->getFileName(),
                ];
            }

            $classData[$className]['timesChanged'] = $metric->getTimesChanged();
            $classData[$className]['score'] += $metric->getScore();
        }

        foreach ($classData as $className => $data) {
            $churnMetric = new ChurnMetrics(
                className: $className,
                file: $data['file'],
                score: $data['score'],
                timesChanged: $data['timesChanged'],
                churn: 0.0 // Will be calculated later
            );
            $collection->add($churnMetric);
        }

        return $collection;
    }

    /**
     * Get coverage for a class, normalizing the class name
     *
     * @param string $className
     * @param CoverageReportReaderInterface $coverageReader
     * @return float Coverage value between 0.0 and 1.0
     */
    private function getCoverageForClass(string $className, CoverageReportReaderInterface $coverageReader): float
    {
        // Remove leading backslash for coverage lookup
        $lookupClassName = ltrim($className, '\\');
        $coverage = $coverageReader->getLineCoverage($lookupClassName);

        // Return coverage or 0.0 if not found
        return $coverage ?? 0.0;
    }

    /**
     * Calculate risk level based on churn and coverage
     *
     * @param float $churn
     * @param float $coverage
     * @return string Risk level: CRITICAL, HIGH, MEDIUM, or LOW
     */
    private function calculateRiskLevel(float $churn, float $coverage): string
    {
        if ($churn > 30 && $coverage < 0.5) {
            return 'CRITICAL';
        }

        if ($churn > 20 && $coverage < 0.7) {
            return 'HIGH';
        }

        if ($churn > 10 && $coverage < 0.8) {
            return 'MEDIUM';
        }

        return 'LOW';
    }
}
