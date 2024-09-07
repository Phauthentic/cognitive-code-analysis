<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Command\Presentation;

use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollection;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class CognitiveMetricTextRenderer
{
    protected float $scoreThreshold = 0.5;

    /**
     * @var array<string>
     */
    protected array $keys = [
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
     * @var array<string>
     */
    protected array $tableHeaders = [
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

    public function render(CognitiveMetricsCollection $metricsCollection, OutputInterface $output): void
    {
        $groupedByClass = $metricsCollection->groupBy('class');

        foreach ($groupedByClass as $className => $metrics) {
            $output->writeln("<info>Class: $className</info>");
            $this->renderTable($output, $metrics);
            $output->writeln("");
        }
    }

    protected function renderTable(OutputInterface $output, CognitiveMetricsCollection $metricsCollection): void
    {
        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders($this->tableHeaders);

        $rows = [];
        foreach ($metricsCollection as $metric) {
            $rows[] = $this->prepareTableRow($metric);
            ;
        }

        $table->setRows($rows);
        $table->render();
    }

    /**
     * @param CognitiveMetrics $metrics
     * @return array<string, mixed>se
     */
    protected function prepareTableRow(CognitiveMetrics $metrics): array
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
            'score' => $metrics->getScore() > $this->scoreThreshold ? '<error>' . $metrics->getScore() . '</error>' : '<info>' . $metrics->getScore() . '</info>',
        ];

        return $this->formatValues($row, $metrics);
    }

    /**
     * @param array<string, mixed> $row
     * @param CognitiveMetrics $metrics
     * @return array<string, mixed>
     */
    protected function formatValues(array $row, CognitiveMetrics $metrics): array
    {
        foreach ($this->keys as $key) {
            $getMethod = 'get' . $key;
            $getMethodWeight = 'get' . $key . 'Weight';
            $weight = $metrics->{$getMethodWeight}();
            $row[$key] = $metrics->{$getMethod}() . ' (' . round($weight, 3) . ')';
        }

        return $row;
    }
}
