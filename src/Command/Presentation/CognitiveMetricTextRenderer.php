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
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\MetricFormatter;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\TableRowBuilder;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\TableHeaderBuilder;

/**
 *
 */
class CognitiveMetricTextRenderer implements CognitiveMetricTextRendererInterface
{
    private MetricFormatter $formatter;
    private TableRowBuilder $rowBuilder;
    private TableHeaderBuilder $headerBuilder;

    public function __construct(
        private readonly OutputInterface $output,
        private readonly ConfigService $configService,
    ) {
        $config = $this->configService->getConfig();
        $this->formatter = new MetricFormatter($config);
        $this->rowBuilder = new TableRowBuilder($this->formatter, $config);
        $this->headerBuilder = new TableHeaderBuilder($config);
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
        $config = $this->configService->getConfig();

        if ($config->groupByClass) {
            $this->renderGroupedByClass($metricsCollection, $config);
            return;
        }

        $this->renderAllMethodsInSingleTable($metricsCollection, $config);
    }

    /**
     * @param CognitiveMetricsCollection $metricsCollection
     * @param CognitiveConfig $config
     * @throws CognitiveAnalysisException
     */
    private function renderGroupedByClass(CognitiveMetricsCollection $metricsCollection, CognitiveConfig $config): void
    {
        $groupedByClass = $metricsCollection->groupBy('class');

        foreach ($groupedByClass as $className => $metrics) {
            if (count($metrics) === 0) {
                continue;
            }

            $rows = $this->buildRowsForClass($metrics, $config);
            if (count($rows) > 0) {
                $filename = $this->getFilenameFromMetrics($metrics);
                $this->renderTable((string)$className, $rows, $filename);
            }
        }
    }

    /**
     * Build rows for a specific class
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildRowsForClass(CognitiveMetricsCollection $metrics, CognitiveConfig $config): array
    {
        $rows = [];
        foreach ($metrics as $metric) {
            if ($this->metricExceedsThreshold($metric, $config)) {
                continue;
            }
            $rows[] = $this->rowBuilder->buildRow($metric);
        }
        return $rows;
    }

    /**
     * Get filename from the first metric in the collection
     */
    private function getFilenameFromMetrics(CognitiveMetricsCollection $metrics): string
    {
        foreach ($metrics as $metric) {
            return $metric->getFileName();
        }
        return '';
    }

    /**
     * @param CognitiveMetricsCollection $metricsCollection
     * @param CognitiveConfig $config
     * @throws CognitiveAnalysisException
     */
    private function renderAllMethodsInSingleTable(CognitiveMetricsCollection $metricsCollection, CognitiveConfig $config): void
    {
        $rows = $this->buildRowsForSingleTable($metricsCollection, $config);
        $totalMethods = count($rows);

        if ($totalMethods > 0) {
            $this->renderSingleTable($rows, $totalMethods);
        }
    }

    /**
     * Build rows for single table display
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildRowsForSingleTable(CognitiveMetricsCollection $metricsCollection, CognitiveConfig $config): array
    {
        $rows = [];
        foreach ($metricsCollection as $metric) {
            if ($this->metricExceedsThreshold($metric, $config)) {
                continue;
            }
            $rows[] = $this->rowBuilder->buildRowWithClassInfo($metric);
        }
        return $rows;
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
     * @param array<int, mixed> $rows
     * @param int $totalMethods
     */
    private function renderSingleTable(array $rows, int $totalMethods): void
    {
        $table = new Table($this->output);
        $table->setStyle('box');
        $table->setHeaders($this->getSingleTableHeaders());

        $this->output->writeln("<info>All Methods ($totalMethods total)</info>");

        $table->setRows($rows);
        $table->render();

        $this->output->writeln("");
    }

    /**
     * @return string[]
     */
    private function getTableHeaders(): array
    {
        return $this->headerBuilder->getGroupedTableHeaders();
    }

    /**
     * @return string[]
     */
    private function getSingleTableHeaders(): array
    {
        return $this->headerBuilder->getSingleTableHeaders();
    }
}
