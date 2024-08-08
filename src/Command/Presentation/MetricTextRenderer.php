<?php

declare(strict_types=1);

namespace Phauthentic\CodeQuality\Command\Presentation;

use Phauthentic\CodeQuality\Business\Metrics;
use Phauthentic\CodeQuality\Business\MetricsCollection;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class MetricTextRenderer
{
    public function render(MetricsCollection $metricsCollection, OutputInterface $output): void
    {
        $groupedByClass = $metricsCollection->groupBy('class');

        foreach ($groupedByClass as $className => $metrics) {
            $output->writeln("<info>Class: $className</info>");

            $table = new Table($output);
            $table->setStyle('box');
            $table->setHeaders($this->getTableHeaders());

            $rows = [];
            foreach ($metrics as $metric) {
                $row = $this->prepareTableRow($metric->getMethod(), $metric);
                $rows[] = $row;
            }

            $table->setRows($rows);
            $table->render();
            $output->writeln("");
        }
    }

    /**
     * @return string[]
     */
    protected function getTableHeaders(): array
    {
        return [
            'Method Name',
            '# Lines',
            '# Arguments',
            '# Returns',
            '# Variables',
            '# Property Accesses',
            '# If',
            'If Nesting Level',
            '# Else',
            'Cognitive Complexity'
        ];
    }

    /**
     * @param string $methodName
     * @param Metrics $metrics
     * @return array<string, mixed>
     */
    protected function prepareTableRow(string $methodName, Metrics $metrics): array
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
            'score' => $metrics->getScore()
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
            $methodName1 = 'get' . $key;
            $methodName = 'get' . $key . 'Weight';
            $weight = $metrics->{$methodName}();
            $row[$key] = $metrics->{$methodName1}() . ' (' . round($weight, 3) . ')';
        }

        return $row;
    }
}
