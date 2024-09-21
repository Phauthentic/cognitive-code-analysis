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
    /**
     * @param CognitiveMetricsCollection $metricsCollection
     * @param array<int, array<string, mixed>> $baseline
     * @param OutputInterface $output
     */
    public function render(CognitiveMetricsCollection $metricsCollection, array $baseline, OutputInterface $output): void
    {
        $groupedByClass = $metricsCollection->groupBy('class');

        foreach ($groupedByClass as $className => $metrics) {
            $output->writeln("<info>Class: $className</info>");

            $table = new Table($output);
            $table->setStyle('box');
            $table->setHeaders($this->getTableHeaders());

            $rows = [];
            foreach ($metrics as $metric) {
                $row = $this->prepareTableRow($metric, $baseline);
                $rows[] = $row;
            }

            $table->setRows($rows);
            $table->render();

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
     * @param array<int, array<string, mixed>> $baseline
     * @return array<string, mixed>
     */
    protected function prepareTableRow(CognitiveMetrics $metrics, array $baseline): array
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
            $row = $this->addDelta($row, $metrics, $baseline, $key, $weight);
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     * @param CognitiveMetrics $metrics
     * @param array<int, array<string, mixed>> $baseline
     * @param string $key
     * @param float $weight
     * @return array<string, mixed>
     */
    private function addDelta(
        array $row,
        CognitiveMetrics $metrics,
        array $baseline,
        string $key,
        float $weight
    ): array {
        foreach ($baseline as $classMetrics) {
            if (!isset($classMetrics['class']) || $classMetrics['class'] !== $metrics->getClass()) {
                continue;
            }

            if (!isset($classMetrics['methods'][$metrics->getMethod()])) {
                continue;
            }

            $method = $key . 'Weight';
            if (!isset($classMetrics['methods'][$metrics->getMethod()][$method])) {
                continue;
            }

            $baselineWeight = (float)$classMetrics['methods'][$metrics->getMethod()][$method];
            if ($baselineWeight === $weight) {
                return $row;
            }

            if ($baselineWeight > $weight) {
                $row[$key] .= PHP_EOL . '<info>Δ -' . round($baselineWeight - $weight, 3) . '</info>';
            }

            if ($baselineWeight < $weight) {
                $row[$key] .= PHP_EOL . '<error>Δ +' . round($weight - $baselineWeight, 3)  . '</error>';
            }
        }

        return $row;
    }
}
