<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn;

use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoverageReportReaderInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;

/**
 *
 */
class ChurnCalculator
{
    /**
     * Calculate the churn for each class based on the metrics collection.
     *
     * @param CognitiveMetricsCollection $metricsCollection
     * @param CoverageReportReaderInterface|null $coverageReader
     * @return array<string, array<string, mixed>>
     */
    public function calculate(
        CognitiveMetricsCollection $metricsCollection,
        ?CoverageReportReaderInterface $coverageReader = null
    ): array {
        $classes = [];
        $classes = $this->groupByClasses($metricsCollection, $classes);
        $classes = $this->calculateChurn($classes, $coverageReader);

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
     * @param CoverageReportReaderInterface|null $coverageReader
     * @return array<string, array<string, mixed>>
     */
    public function calculateChurn(array $classes, ?CoverageReportReaderInterface $coverageReader = null): array
    {
        foreach ($classes as $className => $data) {
            // Calculate standard churn
            $classes[$className]['churn'] = $data['timesChanged'] * $data['score'];

            // Add coverage information if available
            if ($coverageReader !== null) {
                $coverage = $this->getCoverageForClass($className, $coverageReader);
                $classes[$className]['coverage'] = $coverage;
                $classes[$className]['riskChurn'] = $data['timesChanged'] * $data['score'] * (1 - $coverage);
                $classes[$className]['riskLevel'] = $this->calculateRiskLevel(
                    $classes[$className]['churn'],
                    $coverage
                );
            } else {
                // Backward compatibility: no coverage data
                $classes[$className]['coverage'] = null;
                $classes[$className]['riskChurn'] = null;
                $classes[$className]['riskLevel'] = null;
            }
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
                    'file' => $metric->getFilename(),
                ];
            }

            $classes[$metric->getClass()]['timesChanged'] = $metric->getTimesChanged();
            $classes[$metric->getClass()]['score'] += $metric->getScore();
        }
        return $classes;
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
