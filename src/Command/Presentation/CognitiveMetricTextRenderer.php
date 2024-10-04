<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use RuntimeException;
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

    public function metricExceedsThreshold(CognitiveMetrics $metric, CognitiveConfig $config): bool
    {
        return
            $config->showOnlyMethodsExceedingThreshold &&
            $metric->getScore() <= $config->scoreThreshold;
    }

    /**
     * @param CognitiveMetricsCollection $metricsCollection
     */
    public function render(CognitiveMetricsCollection $metricsCollection, CognitiveConfig $config): void
    {
        $groupedByClass = $metricsCollection->groupBy('class');

        foreach ($groupedByClass as $className => $metrics) {
            $rows = [];
            foreach ($metrics as $metric) {
                if ($this->metricExceedsThreshold($metric, $config)) {
                    continue;
                }

                $row = $this->prepareTableRow($metric);
                $rows[] = $row;
            }

            if (empty($rows)) {
                continue;
            }

            $this->renderTable((string)$className, $rows);
        }
    }

    /**
     * @param string $className
     * @param array<int, mixed> $rows
     */
    private function renderTable(string $className, array $rows): void
    {
        $table = new Table($this->output);
        $table->setStyle('box');
        $table->setHeaders($this->getTableHeaders());
        $this->output->writeln("<info>Class: $className</info>");

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
     */
    private function prepareTableRow(CognitiveMetrics $metrics): array
    {
        $row = [
            'methodName' => $metrics->getMethod(),
            'lineCount' => $metrics->getLineCount(),
            'argCount' => $metrics->getArgCount(),
            'returnCount' => $metrics->getReturnCount(),
            'variableCount' => $metrics->getVariableCount(),
            'propertyCallCount' => $metrics->getPropertyCallCount(),
            'ifCount' => $metrics->getIfCount(),
            'ifNestingLevel' => $metrics->getIfNestingLevel(),
            'elseCount' => $metrics->getElseCount(),
            'score' => $metrics->getScore() > 0.5 ? '<error>' . $metrics->getScore() . '</error>' : '<info>' . $metrics->getScore() . '</info>',
        ];

        $keys = [
            'lineCount',
            'argCount',
            'returnCount',
            'variableCount',
            'propertyCallCount',
            'ifCount',
            'ifNestingLevel',
            'elseCount',
        ];

        foreach ($keys as $key) {
            $getMethod = 'get' . $key;
            $getMethodWeight = 'get' . $key . 'Weight';
            $getDeltaMethod = 'get' . $key . 'WeightDelta';
            $weight = $metrics->{$getMethodWeight}();
            $row[$key] = $metrics->{$getMethod}() . ' (' . round($weight, 3) . ')';

            if (!method_exists($metrics, $getDeltaMethod)) {
                throw new RuntimeException('Method not found: ' . $getDeltaMethod);
            }

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
}
