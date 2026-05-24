<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Delta;
use Phauthentic\CognitiveCodeAnalysis\Business\Halstead\HalsteadMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cyclomatic\CyclomaticMetrics;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Helper class for building table rows from metrics
 */
class TableRowBuilder
{
    /**
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function __construct(
        private readonly MetricFormatter $formatter,
        private readonly CognitiveConfig $config,
        private readonly bool $hasCoverage = false,
    ) {
    }

    /**
     * Build a table row from metrics without class information
     *
     * @param CognitiveMetrics $metrics
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

        if ($this->hasCoverage) {
            $row = $this->addCoverageValue($metrics, $row);
        }

        // Add deltas for Halstead metrics
        $row = $this->addHalsteadDeltas($metrics, $row);

        // Add deltas for Cyclomatic metrics
        $row = $this->addCyclomaticDeltas($metrics, $row);

        return $row;
    }

    /**
     * Build a table row from metrics with class information
     *
     * @param CognitiveMetrics $metrics
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

        if ($this->hasCoverage) {
            $row = $this->addCoverageValue($metrics, $row);
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

        return $this->extracted($fields, $metrics);
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

        return $this->extracted($fields, $metrics);
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

        $weight = (float)$metrics->{$getMethodWeight}();
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
            return $this->appendToRowCell(
                $row,
                $key,
                PHP_EOL . '<error>Δ +' . round($delta->getValue(), 3) . '</error>'
            );
        }

        return $this->appendToRowCell(
            $row,
            $key,
            PHP_EOL . '<info>Δ -' . $delta->getValue() . '</info>'
        );
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
     * @throws CognitiveAnalysisException
     */
    private function assertDeltaMethodExists(CognitiveMetrics $metrics, string $getDeltaMethod): void
    {
        if (!method_exists($metrics, $getDeltaMethod)) {
            throw new CognitiveAnalysisException('Method not found: ' . $getDeltaMethod);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function addCoverageValue(CognitiveMetrics $metrics, array $row): array
    {
        $coverage = $metrics->getCoverage();
        if ($coverage === null) {
            $row['coverage'] = 'N/A';
            return $row;
        }

        $row['coverage'] = sprintf('%.2f%%', $coverage * 100);
        return $row;
    }

    /**
     * @param array<string, mixed> $fields
     * @param CognitiveMetrics $metrics
     * @return array<string, mixed>
     */
    private function extracted(array $fields, CognitiveMetrics $metrics): array
    {
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
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function addHalsteadDeltas(CognitiveMetrics $metrics, array $row): array
    {
        if (!$this->config->showHalsteadComplexity || !isset($row['halsteadVolume'])) {
            return $row;
        }

        $volumeDelta = $metrics->getHalsteadVolumeDelta();
        if ($volumeDelta !== null && !$volumeDelta->hasNotChanged()) {
            $row = $this->appendToRowCell($row, 'halsteadVolume', $this->formatDelta($volumeDelta));
        }

        $difficultyDelta = $metrics->getHalsteadDifficultyDelta();
        if ($difficultyDelta !== null && !$difficultyDelta->hasNotChanged()) {
            $row = $this->appendToRowCell($row, 'halsteadDifficulty', $this->formatDelta($difficultyDelta));
        }

        $effortDelta = $metrics->getHalsteadEffortDelta();
        if ($effortDelta !== null && !$effortDelta->hasNotChanged()) {
            $row = $this->appendToRowCell($row, 'halsteadEffort', $this->formatDelta($effortDelta));
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function addCyclomaticDeltas(CognitiveMetrics $metrics, array $row): array
    {
        if (!$this->config->showCyclomaticComplexity || !isset($row['cyclomaticComplexity'])) {
            return $row;
        }

        $complexityDelta = $metrics->getCyclomaticComplexityDelta();
        if ($complexityDelta !== null && !$complexityDelta->hasNotChanged()) {
            $row = $this->appendToRowCell($row, 'cyclomaticComplexity', $this->formatDelta($complexityDelta));
        }

        return $row;
    }

    private function formatDelta(Delta $delta): string
    {
        if ($delta->hasIncreased()) {
            return PHP_EOL . '<error>Δ +' . round($delta->getValue(), 3) . '</error>';
        }
        return PHP_EOL . '<info>Δ ' . round($delta->getValue(), 3) . '</info>';
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function appendToRowCell(array $row, string $key, string $suffix): array
    {
        $value = $row[$key] ?? '';
        if (!is_string($value)) {
            throw new CognitiveAnalysisException(sprintf('Expected string value for row key "%s".', $key));
        }

        $row[$key] = $value . $suffix;

        return $row;
    }
}
