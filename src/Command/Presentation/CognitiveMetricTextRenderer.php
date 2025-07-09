<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class CognitiveMetricTextRenderer
{
    public function __construct(
        private readonly OutputInterface $output
    ) {
    }

    private function metricExceedsThreshold(CognitiveMetrics $metric, CognitiveConfig $config): bool
    {
        return
            $config->showOnlyMethodsExceedingThreshold &&
            $metric->getScore() <= $config->scoreThreshold;
    }

    /**
     * @param CognitiveMetricsCollection $metricsCollection
     * @param CognitiveConfig $config
     * @throws CognitiveAnalysisException
     */
    public function render(CognitiveMetricsCollection $metricsCollection, CognitiveConfig $config): void
    {
        $groupedByClass = $metricsCollection->groupBy('class');

        foreach ($groupedByClass as $className => $metrics) {
            if (count($metrics) === 0) {
                continue;
            }

            $rows = [];
            $filename = '';

            foreach ($metrics as $metric) {
                if ($this->metricExceedsThreshold($metric, $config)) {
                    continue;
                }

                $rows[] = $this->prepareTableRows($metric);
                $filename = $metric->getFileName();
            }

            $this->renderTable((string)$className, $rows, $filename);
        }
    }

    /**
     * @param string $className
     * @param array<int, mixed> $rows
     * @param string $filename
     */
    private function renderTable(string $className, array $rows, string $filename): void
    {
        $table = new Table($this->output);
        $table->setStyle('box');
        $table->setHeaders($this->getTableHeaders());

        $this->output->writeln("<info>Class: $className</info>");
        $this->output->writeln("<info>File: $filename</info>");

        $table->setRows($rows);
        $table->render();

        $this->output->writeln("");
    }

    /**
     * @return string[]
     */
    private function getTableHeaders(): array
    {
        return [
            "Method Name",
            "Lines",
            "Arguments",
            "Returns",
            "Variables",
            "Property\nAccesses",
            "If",
            "If Nesting\nLevel",
            "Else",
            "Cognitive\nComplexity",
            "Halstead\nVolume",
            "Halstead\nDifficulty",
            "Halstead\nEffort",
            "Cyclomatic\nComplexity"
        ];
    }

    /**
     * @param CognitiveMetrics $metrics
     * @return array<string, mixed>
     * @throws CognitiveAnalysisException
     */
    private function prepareTableRows(CognitiveMetrics $metrics): array
    {
        $row = $this->metricsToArray($metrics);
        $keys = $this->getKeys();

        foreach ($keys as $key) {
            $row = $this->roundWeighs($key, $metrics, $row);

            $getDeltaMethod = 'get' . $key . 'WeightDelta';
            $this->assertDeltaMethodExists($metrics, $getDeltaMethod);

            $delta = $metrics->{$getDeltaMethod}();
            if ($delta === null || $delta->hasNotChanged()) {
                continue;
            }

            if ($delta->hasIncreased()) {
                $row[$key] .= PHP_EOL . '<error>Δ +' . round($delta->getValue(), 3) . '</error>';
                continue;
            }

            $row[$key] .= PHP_EOL . '<info>Δ -' . $delta->getValue() . '</info>';
        }

        // No delta for halstead/cyclomatic
        return $row;
    }

    /**
     * @return string[]
     */
    private function getKeys(): array
    {
        return [
            'lineCount',
            'argCount',
            'returnCount',
            'variableCount',
            'propertyCallCount',
            'ifCount',
            'ifNestingLevel',
            'elseCount',
            // No keys for halstead/cyclomatic, handled separately in metricsToArray
        ];
    }

    /**
     * @param CognitiveMetrics $metrics
     * @return array<string, mixed>
     */
    private function metricsToArray(CognitiveMetrics $metrics): array
    {
        $halstead = $metrics->getHalstead();
        $cyclomatic = $metrics->getCyclomatic();

        $halsteadVolume = $this->formatHalsteadVolume($halstead);
        $halsteadDifficulty = $this->formatHalsteadDifficulty($halstead);
        $halsteadEffort = $this->formatHalsteadEffort($halstead);
        $cyclomaticComplexity = $this->formatCyclomaticComplexity($cyclomatic);

        return [
            'methodName' => $metrics->getMethod(),
            'lineCount' => $metrics->getLineCount(),
            'argCount' => $metrics->getArgCount(),
            'returnCount' => $metrics->getReturnCount(),
            'variableCount' => $metrics->getVariableCount(),
            'propertyCallCount' => $metrics->getPropertyCallCount(),
            'ifCount' => $metrics->getIfCount(),
            'ifNestingLevel' => $metrics->getIfNestingLevel(),
            'elseCount' => $metrics->getElseCount(),
            'score' => $this->formatScore($metrics->getScore()),
            'halsteadVolume' => $halsteadVolume,
            'halsteadDifficulty' => $halsteadDifficulty,
            'halsteadEffort' => $halsteadEffort,
            'cyclomaticComplexity' => $cyclomaticComplexity,
        ];
    }

    private function formatScore(float $score): string
    {
        return $score > 0.5
            ? '<error>' . $score . '</error>'
            : '<info>' . $score . '</info>';
    }

    private function formatHalsteadVolume($halstead): string
    {
        if (!$halstead) {
            return '-';
        }
        $value = round($halstead->getVolume(), 3);
        if ($value >= 1000) {
            return '<error>' . $value . '</error>';
        }
        if ($value >= 100) {
            return '<comment>' . $value . '</comment>';
        }
        return (string)$value;
    }

    private function formatHalsteadDifficulty($halstead): string
    {
        if (!$halstead) {
            return '-';
        }
        $value = round($halstead->difficulty, 3);
        if ($value >= 50) {
            return '<error>' . $value . '</error>';
        }
        if ($value >= 10) {
            return '<comment>' . $value . '</comment>';
        }
        return (string)$value;
    }

    private function formatHalsteadEffort($halstead): string
    {
        if (!$halstead) {
            return '-';
        }
        $value = round($halstead->effort, 3);
        if ($value >= 5000) {
            return '<error>' . $value . '</error>';
        }
        if ($value >= 500) {
            return '<comment>' . $value . '</comment>';
        }
        return (string)$value;
    }

    private function formatCyclomaticComplexity($cyclomatic): string
    {
        if (!$cyclomatic) {
            return '-';
        }
        $complexity = $cyclomatic->complexity;
        $risk = $cyclomatic->riskLevel ?? '';
        if ($risk === '') {
            return (string)$complexity;
        }
        $riskColored = $this->colorCyclomaticRisk($risk);
        return $complexity . ' (' . $riskColored . ')';
    }

    private function colorCyclomaticRisk(string $risk): string
    {
        $riskLower = strtolower($risk);
        if ($riskLower === 'medium') {
            return '<comment>' . $risk . '</comment>';
        }
        if ($riskLower === 'high') {
            return '<error>' . $risk . '</error>';
        }
        return $risk;
    }

    /**
     * @param CognitiveMetrics $metrics
     * @param string $getDeltaMethod
     * @return void
     * @throws CognitiveAnalysisException
     */
    private function assertDeltaMethodExists(CognitiveMetrics $metrics, string $getDeltaMethod): void
    {
        if (!method_exists($metrics, $getDeltaMethod)) {
            throw new CognitiveAnalysisException('Method not found: ' . $getDeltaMethod);
        }
    }

    /**
     * @param string $key
     * @param CognitiveMetrics $metrics
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function roundWeighs(string $key, CognitiveMetrics $metrics, array $row): array
    {
        $getMethod = 'get' . $key;
        $getMethodWeight = 'get' . $key . 'Weight';

        $weight = $metrics->{$getMethodWeight}();
        $row[$key] = $metrics->{$getMethod}() . ' (' . round($weight, 3) . ')';

        return $row;
    }
}
