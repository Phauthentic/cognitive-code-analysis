<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Command\Presentation;

use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollection;
use RuntimeException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class CognitiveMetricTextRenderer
{
    /**
     * @param CognitiveMetricsCollection $metricsCollection
     * @param OutputInterface $output
     */
    public function render(CognitiveMetricsCollection $metricsCollection, OutputInterface $output): void
    {
        $groupedByClass = $metricsCollection->groupBy('class');

        foreach ($groupedByClass as $className => $metrics) {
            $output->writeln("<info>Class: $className</info>");

            $table = new Table($output);
            $table->setStyle('box');
            $table->setHeaders($this->getTableHeaders());

            $rows = [];
            foreach ($metrics as $metric) {
                $row = $this->prepareTableRow($metric);
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

            $row[$key] .= PHP_EOL . '<info>Δ ' . $delta->getValue() . '</info>';
        }

        return $row;
    }
}
