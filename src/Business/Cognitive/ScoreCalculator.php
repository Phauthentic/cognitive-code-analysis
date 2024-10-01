<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

/**
 *
 */
class ScoreCalculator
{
    /**
     * @var string[]
     */
    private array $combinedMetrics = [
        'lineCount',
        'argCount',
        'returnCount',
        'variableCount',
        'propertyCallCount',
        'ifCount',
        'ifNestingLevel',
        'elseCount',
    ];

    /**
     * @param CognitiveMetrics $metrics
     * @param array<string, mixed> $metricConfiguration
     * @return void
     */
    public function calculate(CognitiveMetrics $metrics, array $metricConfiguration = []): void
    {
        $metricConfiguration = $metricConfiguration['metrics'];

        // List of metric types to process
        $metricTypes = [
            'LineCount' => 'lineCount',
            'ArgCount' => 'argCount',
            'ReturnCount' => 'returnCount',
            'VariableCount' => 'variableCount',
            'IfCount' => 'ifCount',
            'IfNestingLevel' => 'ifNestingLevel',
            'PropertyCallCount' => 'propertyCallCount',
            'ElseCount' => 'elseCount',
        ];

        // Calculate and set weights for each metric type
        $this->calculateMetricWeights($metricTypes, $metrics, $metricConfiguration);

        // Calculate the overall score
        $this->calculateScore($metrics);
    }

    private function calculateScore(CognitiveMetrics $metrics): void
    {
        $score = 0;
        foreach ($this->combinedMetrics as $metric) {
            $methodName = 'get' . $metric . 'Weight';
            $score += $metrics->{$methodName}();
        }

        $metrics->setScore(round($score, 3));
    }

    /**
     * @param array<string, string> $metricTypes
     * @param CognitiveMetrics $metrics
     * @param array<string, mixed> $config
     * @return void
     */
    private function calculateMetricWeights(array $metricTypes, CognitiveMetrics $metrics, array $config): void
    {
        foreach ($metricTypes as $methodSuffix => $configKey) {
            $getMethod = 'get' . $methodSuffix;
            $setMethod = 'set' . $methodSuffix . 'Weight';

            $metrics->{$setMethod}(
                $this->calculateLogWeight(
                    $metrics->{$getMethod}(),
                    $config[$configKey]['threshold'],
                    $config[$configKey]['scale']
                )
            );
        }
    }

    /**
     * Calculate a logarithmic weight for a given value based on a threshold.
     *
     * This function computes a weight that increases logarithmically as the input
     * value exceeds a specified threshold. The result is 0 when the value is
     * less than or equal to the threshold. When the value is greater than the
     * threshold, the weight is calculated using a logarithmic function that
     * controls the rate of increase.
     *
     * The formula used for the calculation is:
     *
     *     weight = log(1 + (value - threshold) / scale, base)
     *
     * - If `value` is less than or equal to `threshold`, the function returns `0.0`.
     * - If `value` exceeds `threshold`, the function returns a logarithmic weight
     *   based on the difference between `value` and `threshold`, scaled by the
     *   `scale` parameter.
     * - The logarithm base can be adjusted using the `base` parameter (default is
     *   natural logarithm with base `M_E`).
     *
     * Parameters:
     * - `value` (float): The actual value being evaluated.
     * - `threshold` (float): The threshold value to compare against.
     * - `base` (float): The base of the logarithm. Default is natural logarithm (base `M_E`).
     * - `scale` (float): A scaling factor to control the steepness of the logarithmic curve. Default is `1.0`.
     *
     * Returns:
     * - `float`: The calculated logarithmic weight. Returns `0.0` if the `value`
     *   is less than or equal to the `threshold`.
     *
     * Example:
     *
     * ```php
     * $value = 75.0;
     * $threshold = 50.0;
     * $scale = 10.0;
     * $weight = calculateLogWeight($value, $threshold, M_E, $scale);
     * echo $weight;  // Output: 2.302585
     * ```
     *
     * In this example, since the `value` (75) is greater than the `threshold` (50),
     * the function calculates the logarithmic weight as log(1 + (75 - 50) / 10, M_E)
     * which results in approximately 2.302585.
     */
    private function calculateLogWeight(float $value, float $threshold, float $scale = 1.0): float
    {
        if ($value <= $threshold) {
            return 0.0;
        }

        return log(1 + ($value - $threshold) / $scale);
    }
}
