<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Halstead\HalsteadMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cyclomatic\CyclomaticMetrics;

/**
 *
 */
class CognitiveMetricTextRenderer
{
    public function __construct(
        private readonly OutputInterface $output,
        private readonly ConfigService $configService,
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
     * @throws CognitiveAnalysisException
     */
    public function render(CognitiveMetricsCollection $metricsCollection): void
    {
        $groupedByClass = $metricsCollection->groupBy('class');
        $config = $this->configService->getConfig();

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

            if (count($rows) > 0) {
                $this->renderTable((string)$className, $rows, $filename);
            }
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
        $fields = [
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
        ];

        $fields = $this->addHalsteadHeaders($fields);
        $fields = $this->addCyclomaticHeaders($fields);

        return $fields;
    }

    /**
     * @param array<string> $fields
     * @return array<string>
     */
    private function addHalsteadHeaders(array $fields): array
    {
        if ($this->configService->getConfig()->showHalsteadComplexity) {
            $fields[] = "Halstead\nVolume";
            $fields[] = "Halstead\nDifficulty";
            $fields[] = "Halstead\nEffort";
        }

        return $fields;
    }

    /**
     * @param array<string> $fields
     * @return array<string>
     */
    private function addCyclomaticHeaders(array $fields): array
    {
        if ($this->configService->getConfig()->showCyclomaticComplexity) {
            $fields[] = "Cyclomatic\nComplexity";
        }

        return $fields;
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
        $fields = [
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
        ];

        $fields = $this->addHalsteadFields($fields, $metrics->getHalstead());
        $fields = $this->addCyclomaticFields($fields, $metrics->getCyclomatic());

        return $fields;
    }

    /**
     * @param array<string, mixed> $fields
     * @param HalsteadMetrics|null $halstead
     * @return array<string, mixed>
     */
    private function addHalsteadFields(array $fields, ?HalsteadMetrics $halstead): array
    {
        if ($this->configService->getConfig()->showHalsteadComplexity) {
            $fields['halsteadVolume'] = $this->formatHalsteadVolume($halstead);
            $fields['halsteadDifficulty'] = $this->formatHalsteadDifficulty($halstead);
            $fields['halsteadEffort'] = $this->formatHalsteadEffort($halstead);
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $fields
     * @param CyclomaticMetrics|null $cyclomatic
     * @return array<string, mixed>
     */
    private function addCyclomaticFields(array $fields, ?CyclomaticMetrics $cyclomatic): array
    {
        if ($this->configService->getConfig()->showCyclomaticComplexity) {
            $fields['cyclomaticComplexity'] = $this->formatCyclomaticComplexity($cyclomatic);
        }

        return $fields;
    }

    private function formatScore(float $score): string
    {
        return $score > $this->configService->getConfig()->scoreThreshold
            ? '<error>' . $score . '</error>'
            : '<info>' . $score . '</info>';
    }

    private function formatHalsteadVolume(?HalsteadMetrics $halstead): string
    {
        if (!$halstead) {
            return '-';
        }

        $value = round($halstead->getVolume(), 3);

        return match (true) {
            $value >= 1000 => '<error>' . $value . '</error>',
            $value >= 100 => '<comment>' . $value . '</comment>',
            default => (string)$value,
        };
    }

    private function formatHalsteadDifficulty(?HalsteadMetrics $halstead): string
    {
        if (!$halstead) {
            return '-';
        }
        $value = round($halstead->difficulty, 3);

        return match (true) {
            $value >= 50 => '<error>' . $value . '</error>',
            $value >= 10 => '<comment>' . $value . '</comment>',
            default => (string)$value,
        };
    }

    private function formatHalsteadEffort(?HalsteadMetrics $halstead): string
    {
        if (!$halstead) {
            return '-';
        }
        $value = round($halstead->effort, 3);

        return match (true) {
            $value >= 5000 => '<error>' . $value . '</error>',
            $value >= 500 => '<comment>' . $value . '</comment>',
            default => (string)$value,
        };
    }

    private function formatCyclomaticComplexity(?CyclomaticMetrics $cyclomatic): string
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
        return match (strtolower($risk)) {
            'medium' => '<comment>' . $risk . '</comment>',
            'high' => '<error>' . $risk . '</error>',
            default => $risk,
        };
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
