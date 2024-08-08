<?php

declare(strict_types=1);

namespace Phauthentic\CodeQuality\Business;

use InvalidArgumentException;

/**
 *
 */
class ScoreCalculator
{
    /**
     * @var float[]
     */
    private array $scale = [
        'line_count' => 2.0,
        'arg_count' => 1.0,
        'return_count' => 5.0,
        'variable_count' => 5.0,
        'property_call_count' => 15.0,
        'if_nesting_level' => 1.0,
        'else_count' => 1.0,
        'if_count' => 1.0
    ];

    /**
     * @var int[]
     */
    private array $thresholds = [
        'line_count' => 60,
        'arg_count' => 4,
        'return_count' => 2,
        'variable_count' => 2,
        'property_call_count' => 4,
        'if_nesting_level' => 2,
        'else_count' => 1,
        'if_count' => 3
    ];

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

    public function calculate(Metrics $metrics): void
    {
        $metrics->setLineCountWeight(
            $this->calculateLogWeight(
                (float) $metrics->getLineCount(),
                $this->thresholds['line_count'],
                $this->scale['line_count']
            )
        );

        $metrics->setArgCountWeight(
            $this->calculateLogWeight(
                (float) $metrics->getArgCount(),
                $this->thresholds['arg_count'],
                $this->scale['arg_count']
            )
        );

        $metrics->setReturnCountWeight(
            $this->calculateLogWeight(
                (float) $metrics->getReturnCount(),
                $this->thresholds['return_count'],
                $this->scale['return_count']
            )
        );

        $metrics->setVariableCountWeight(
            $this->calculateLogWeight(
                (float) $metrics->getVariableCount(),
                $this->thresholds['variable_count'],
                $this->scale['variable_count']
            )
        );

        $metrics->setIfCountWeight(
            $this->calculateLogWeight(
                (float) $metrics->getIfCount(),
                $this->thresholds['if_count'],
                $this->scale['if_count']
            )
        );

        $metrics->setIfNestingLevelWeight(
            $this->calculateLogWeight(
                (float) $metrics->getIfNestingLevel(),
                $this->thresholds['if_nesting_level'],
                $this->scale['if_nesting_level']
            )
        );

        $metrics->setPropertyCallCountWeight(
            $this->calculateLogWeight(
                (float) $metrics->getPropertyCallCount(),
                $this->thresholds['property_call_count'],
                $this->scale['property_call_count']
            )
        );

        $metrics->setElseCountWeight(
            $this->calculateLogWeight(
                (float) $metrics->getElseCount(),
                $this->thresholds['else_count'],
                $this->scale['else_count']
            )
        );

        $score = 0;
        foreach ($this->combinedMetrics as $metric) {
            $methodName = 'get' . $metric . 'Weight';
            $score += $metrics->{$methodName}();
        }

        $metrics->setScore(round($score, 3));
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
    private function calculateLogWeight(float $value, float $threshold, float $scale = 1.0, float $base = M_E): float
    {
        if ($value <= $threshold) {
            return 0.0;
        }

        return log(1 + ($value - $threshold) / $scale, $base);
    }

    private function assertThresholdIsGreaterThanZero(float $threshold): void
    {
        if ($threshold <= 0) {
            throw new InvalidArgumentException('Threshold must be greater than zero.');
        }
    }
}
