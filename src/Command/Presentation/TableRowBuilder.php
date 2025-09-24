<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Halstead\HalsteadMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cyclomatic\CyclomaticMetrics;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Helper class for building table rows from metrics
 */
class TableRowBuilder
{
    public function __construct(
        private readonly MetricFormatter $formatter,
        private readonly CognitiveConfig $config
    ) {
    }

    /**
     * Build a table row from metrics without class information
     *
     * @return array<string, mixed>
     */
    public function buildRow(CognitiveMetrics $metrics): array
    {
        $row = $this->metricsToArray($metrics);
        $keys = $this->getKeys();

        foreach ($keys as $key) {
            $row = $this->addWeightedValue($key, $metrics, $row);
            $row = $this->addDelta($key, $metrics, $row);
        }

        return $row;
    }

    /**
     * Build a table row from metrics with class information
     *
     * @return array<string, mixed>
     */
    public function buildRowWithClassInfo(CognitiveMetrics $metrics): array
    {
        $row = $this->metricsToArrayWithClassInfo($metrics);
        $keys = $this->getKeys();

        foreach ($keys as $key) {
            $row = $this->addWeightedValue($key, $metrics, $row);
            $row = $this->addDelta($key, $metrics, $row);
        }

        return $row;
    }

    /**
     * Convert metrics to array format
     *
     * @return array<string, mixed>
     */
    private function metricsToArray(CognitiveMetrics $metrics): array
    {
        $fields = [
            'methodName' => $metrics->getMethod(),
        ];

        if ($this->config->showDetailedCognitiveMetrics) {
            $fields = array_merge($fields, [
                'lineCount' => $metrics->getLineCount(),
                'argCount' => $metrics->getArgCount(),
                'returnCount' => $metrics->getReturnCount(),
                'variableCount' => $metrics->getVariableCount(),
                'propertyCallCount' => $metrics->getPropertyCallCount(),
                'ifCount' => $metrics->getIfCount(),
                'ifNestingLevel' => $metrics->getIfNestingLevel(),
                'elseCount' => $metrics->getElseCount(),
            ]);
        }

        $fields['score'] = $this->formatter->formatScore($metrics->getScore());

        $fields = $this->addHalsteadFields($fields, $metrics->getHalstead());
        $fields = $this->addCyclomaticFields($fields, $metrics->getCyclomatic());

        return $fields;
    }

    /**
     * Convert metrics to array format with class information
     *
     * @return array<string, mixed>
     */
    private function metricsToArrayWithClassInfo(CognitiveMetrics $metrics): array
    {
        $fields = [
            'className' => $metrics->getClass(),
            'methodName' => $metrics->getMethod(),
        ];

        if ($this->config->showDetailedCognitiveMetrics) {
            $fields = array_merge($fields, [
                'lineCount' => $metrics->getLineCount(),
                'argCount' => $metrics->getArgCount(),
                'returnCount' => $metrics->getReturnCount(),
                'variableCount' => $metrics->getVariableCount(),
                'propertyCallCount' => $metrics->getPropertyCallCount(),
                'ifCount' => $metrics->getIfCount(),
                'ifNestingLevel' => $metrics->getIfNestingLevel(),
                'elseCount' => $metrics->getElseCount(),
            ]);
        }

        $fields['score'] = $this->formatter->formatScore($metrics->getScore());

        $fields = $this->addHalsteadFields($fields, $metrics->getHalstead());
        $fields = $this->addCyclomaticFields($fields, $metrics->getCyclomatic());

        return $fields;
    }

    /**
     * Add Halstead fields to the array
     *
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function addHalsteadFields(array $fields, ?HalsteadMetrics $halstead): array
    {
        if ($this->config->showHalsteadComplexity) {
            $fields['halsteadVolume'] = $this->formatter->formatHalsteadVolume($halstead);
            $fields['halsteadDifficulty'] = $this->formatter->formatHalsteadDifficulty($halstead);
            $fields['halsteadEffort'] = $this->formatter->formatHalsteadEffort($halstead);
        }

        return $fields;
    }

    /**
     * Add Cyclomatic fields to the array
     *
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function addCyclomaticFields(array $fields, ?CyclomaticMetrics $cyclomatic): array
    {
        if ($this->config->showCyclomaticComplexity) {
            $fields['cyclomaticComplexity'] = $this->formatter->formatCyclomaticComplexity($cyclomatic);
        }

        return $fields;
    }

    /**
     * Add weighted value to the row
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function addWeightedValue(string $key, CognitiveMetrics $metrics, array $row): array
    {
        $getMethod = 'get' . $key;
        $getMethodWeight = 'get' . $key . 'Weight';

        $weight = $metrics->{$getMethodWeight}();
        $row[$key] = $metrics->{$getMethod}() . ' (' . round($weight, 3) . ')';

        return $row;
    }

    /**
     * Add delta information to the row
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function addDelta(string $key, CognitiveMetrics $metrics, array $row): array
    {
        $getDeltaMethod = 'get' . $key . 'WeightDelta';
        $this->assertDeltaMethodExists($metrics, $getDeltaMethod);

        $delta = $metrics->{$getDeltaMethod}();
        if ($delta === null || $delta->hasNotChanged()) {
            return $row;
        }

        if ($delta->hasIncreased()) {
            $row[$key] .= PHP_EOL . '<error>Δ +' . round($delta->getValue(), 3) . '</error>';

            return $row;
        }

        $row[$key] .= PHP_EOL . '<info>Δ -' . $delta->getValue() . '</info>';

        return $row;
    }

    /**
     * Get the keys for processing
     *
     * @return array<int, string>
     */
    private function getKeys(): array
    {
        if (!$this->config->showDetailedCognitiveMetrics) {
            return [];
        }

        return [
            'lineCount',
            'argCount',
            'returnCount',
            'variableCount',
            'propertyCallCount',
            'ifCount',
            'ifNestingLevel',
            'elseCount',
        ];
    }

    /**
     * Assert that a delta method exists
     */
    private function assertDeltaMethodExists(CognitiveMetrics $metrics, string $getDeltaMethod): void
    {
        if (!method_exists($metrics, $getDeltaMethod)) {
            throw new CognitiveAnalysisException('Method not found: ' . $getDeltaMethod);
        }
    }
}
