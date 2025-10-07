<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Traits\CoverageDataDetector;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class CognitiveMetricTextRenderer implements CognitiveMetricTextRendererInterface
{
    use CoverageDataDetector;

    private MetricFormatter $formatter;
    private TableRowBuilder $rowBuilder;
    private TableHeaderBuilder $headerBuilder;

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

    /**
     * @param CognitiveMetricsCollection $metricsCollection
     * @param OutputInterface $output
     * @throws CognitiveAnalysisException
     */
    public function render(CognitiveMetricsCollection $metricsCollection, OutputInterface $output): void
    {
        $config = $this->configService->getConfig();

        // Recreate components with current configuration
        $this->formatter = new MetricFormatter($config);
        $this->rowBuilder = new TableRowBuilder($this->formatter, $config);
        $this->headerBuilder = new TableHeaderBuilder($config);

        if ($config->groupByClass) {
            $this->renderGroupedByClass($metricsCollection, $config, $output);
            return;
        }

        $this->renderAllMethodsInSingleTable($metricsCollection, $config, $output);
    }

    /**
     * @param CognitiveMetricsCollection $metricsCollection
     * @param CognitiveConfig $config
     * @param OutputInterface $output
     * @throws CognitiveAnalysisException
     */
    private function renderGroupedByClass(CognitiveMetricsCollection $metricsCollection, CognitiveConfig $config, OutputInterface $output): void
    {
        $hasCoverage = $this->hasCoverageInCollection($metricsCollection);
        $groupedByClass = $metricsCollection->groupBy('class');

        foreach ($groupedByClass as $className => $metrics) {
            if (count($metrics) === 0) {
                continue;
            }

            $rows = $this->buildRowsForClass($metrics, $config, $hasCoverage);
            if (count($rows) > 0) {
                $filename = $this->getFilenameFromMetrics($metrics);
                $this->renderTable((string)$className, $rows, $filename, $hasCoverage, $output);
            }
        }
    }

    /**
     * Build rows for a specific class
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildRowsForClass(CognitiveMetricsCollection $metrics, CognitiveConfig $config, bool $hasCoverage): array
    {
        $rows = [];
        foreach ($metrics as $metric) {
            if ($this->metricExceedsThreshold($metric, $config)) {
                continue;
            }
            $rows[] = $this->rowBuilder->buildRow($metric, $hasCoverage);
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
     * @param OutputInterface $output
     * @throws CognitiveAnalysisException
     */
    private function renderAllMethodsInSingleTable(CognitiveMetricsCollection $metricsCollection, CognitiveConfig $config, OutputInterface $output): void
    {
        $hasCoverage = $this->hasCoverageInCollection($metricsCollection);
        $rows = $this->buildRowsForSingleTable($metricsCollection, $config, $hasCoverage);
        $totalMethods = count($rows);

        if ($totalMethods > 0) {
            $this->renderSingleTable($rows, $totalMethods, $hasCoverage, $output);
        }
    }

    /**
     * Build rows for single table display
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildRowsForSingleTable(CognitiveMetricsCollection $metricsCollection, CognitiveConfig $config, bool $hasCoverage): array
    {
        $rows = [];
        foreach ($metricsCollection as $metric) {
            if ($this->metricExceedsThreshold($metric, $config)) {
                continue;
            }
            $rows[] = $this->rowBuilder->buildRowWithClassInfo($metric, $hasCoverage);
        }
        return $rows;
    }

    /**
     * @param string $className
     * @param array<int, mixed> $rows
     * @param string $filename
     * @param bool $hasCoverage
     * @param OutputInterface $output
     */
    private function renderTable(string $className, array $rows, string $filename, bool $hasCoverage, OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders($this->getTableHeaders($hasCoverage));

        $output->writeln("<info>Class: $className</info>");
        $output->writeln("<info>File: $filename</info>");

        $table->setRows($rows);
        $table->render();

        $output->writeln("");
    }

    /**
     * @param array<int, mixed> $rows
     * @param int $totalMethods
     * @param bool $hasCoverage
     * @param OutputInterface $output
     */
    private function renderSingleTable(array $rows, int $totalMethods, bool $hasCoverage, OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders($this->getSingleTableHeaders($hasCoverage));

        $output->writeln("<info>All Methods ($totalMethods total)</info>");

        $table->setRows($rows);
        $table->render();

        $output->writeln("");
    }

    /**
     * @return string[]
     */
    private function getTableHeaders(bool $hasCoverage = false): array
    {
        return $this->headerBuilder->getGroupedTableHeaders($hasCoverage);
    }

    /**
     * @return string[]
     */
    private function getSingleTableHeaders(bool $hasCoverage = false): array
    {
        return $this->headerBuilder->getSingleTableHeaders($hasCoverage);
    }
}
