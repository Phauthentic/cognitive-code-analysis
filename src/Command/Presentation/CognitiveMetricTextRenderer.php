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
            "Cognitive\nComplexity"
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
        ];
    }

    /**
     * @param CognitiveMetrics $metrics
     * @return array<string, mixed>
     */
    private function metricsToArray(CognitiveMetrics $metrics): array
    {
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
            'score' => $metrics->getScore() > 0.5
                ? '<error>' . $metrics->getScore() . '</error>'
                : '<info>' . $metrics->getScore() . '</info>',
        ];
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
     * @param array $row
     * @return array
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
