<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\CoverageDataDetector;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class CognitiveMetricTextRenderer implements CognitiveMetricTextRendererInterface
{
    use CoverageDataDetector;

    private MetricFormatter $formatter;
    private TableRowBuilder $rowBuilder;
    private TableHeaderBuilder $headerBuilder;
    private bool $hasCoverage = false;

    public function __construct(
        private readonly ConfigService $configService,
    ) {
        // Don't initialize components here - they'll be created with current config when rendering
    }

    private function metricExceedsThreshold(CognitiveMetrics $metric, CognitiveConfig $config): bool
    {
        return
            $config->showOnlyMethodsExceedingThreshold &&
            $metric->getScore() <= $config->scoreThreshold;
    }

    /**
     * Check if any metric in the collection has coverage data
     */
    private function hasCoverageInCollection(CognitiveMetricsCollection $metricsCollection): bool
    {
        foreach ($metricsCollection as $metric) {
            if ($metric->getCoverage() !== null) {
                return true;
            }
        }

        return false;
    }

    public function render(CognitiveMetricsCollection $metricsCollection, OutputInterface $output): void
    {
        $config = $this->configService->getConfig();
        $this->hasCoverage = $this->hasCoverageInCollection($metricsCollection);

        // Recreate components with current configuration
        $this->formatter = new MetricFormatter($config);
        $this->rowBuilder = new TableRowBuilder($this->formatter, $config, $this->hasCoverage);
        $this->headerBuilder = new TableHeaderBuilder($config, $this->hasCoverage);

        if ($config->groupByClass) {
            $this->renderGroupedByClass($metricsCollection, $config, $output);
            return;
        }

        $this->renderAllMethodsInSingleTable($metricsCollection, $config, $output);
    }

    private function renderGroupedByClass(
        CognitiveMetricsCollection $metricsCollection,
        CognitiveConfig $config,
        OutputInterface $output
    ): void {
        $groupedByClass = $metricsCollection->groupBy('class');

        foreach ($groupedByClass as $className => $metrics) {
            if (count($metrics) === 0) {
                continue;
            }

            $rows = $this->buildRowsForClass($metrics, $config);
            if (count($rows) <= 0) {
                continue;
            }

            $filename = $this->getFilenameFromMetrics($metrics);
            $this->renderTable((string)$className, $rows, $filename, $output);
        }
    }

    /**
     * Build rows for a specific class
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildRowsForClass(
        CognitiveMetricsCollection $metrics,
        CognitiveConfig $config
    ): array {
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

    private function renderAllMethodsInSingleTable(
        CognitiveMetricsCollection $metricsCollection,
        CognitiveConfig $config,
        OutputInterface $output
    ): void {
        $rows = $this->buildRowsForSingleTable($metricsCollection, $config);
        $totalMethods = count($rows);

        if ($totalMethods <= 0) {
            return;
        }

        $this->renderSingleTable($rows, $totalMethods, $output);
    }

    /**
     * Build rows for single table display
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildRowsForSingleTable(
        CognitiveMetricsCollection $metricsCollection,
        CognitiveConfig $config
    ): array {
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
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $headers
     * @param array<int, string> $infoLines
     */
    private function renderTableCommon(
        array $rows,
        array $headers,
        array $infoLines,
        OutputInterface $output
    ): void {
        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders($headers);

        foreach ($infoLines as $line) {
            $output->writeln($line);
        }

        $table->setRows($rows);
        $table->render();

        $output->writeln("");
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function renderTable(
        string $className,
        array $rows,
        string $filename,
        OutputInterface $output
    ): void {
        $headers = $this->headerBuilder->getGroupedTableHeaders();
        $infoLines = [
            "<info>Class: $className</info>",
            "<info>File: $filename</info>"
        ];
        $this->renderTableCommon($rows, $headers, $infoLines, $output);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function renderSingleTable(
        array $rows,
        int $totalMethods,
        OutputInterface $output
    ): void {
        $headers = $this->headerBuilder->getSingleTableHeaders();
        $infoLines = [
            "<info>All Methods ($totalMethods total)</info>"
        ];
        $this->renderTableCommon($rows, $headers, $infoLines, $output);
    }
}
