<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Delta;
use Phauthentic\CognitiveCodeAnalysis\Business\Exporter\MarkdownFormatterTrait;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

/**
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
class MarkdownExporter implements DataExporterInterface
{
    use MarkdownFormatterTrait;

    private CognitiveConfig $config;

    public function __construct(CognitiveConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Export metrics to a Markdown file.
     *
     * @param CognitiveMetricsCollection $metrics
     * @param string $filename
     * @return void
     */
    public function export(CognitiveMetricsCollection $metrics, string $filename): void
    {
        $markdown = $this->generateMarkdown($metrics);

        if (file_put_contents($filename, $markdown) === false) {
            throw new CognitiveAnalysisException('Could not write to file');
        }
    }

    /**
     * Escape markdown special characters.
     */
    private function escape(string $string): string
    {
        return $this->escapeMarkdown($string);
    }

    /**
     * Generate a metric cell with optional delta.
     */
    private function generateMetricCell(int $count, float $weight, ?Delta $delta): string
    {
        $cell = $count . ' (' . $this->formatNumber($weight) . ')';

        if ($delta !== null && !$delta->hasNotChanged()) {
            $deltaValue = $this->formatNumber($delta->getValue());
            $deltaSymbol = $delta->hasIncreased() ? '↑' : '↓';
            $cell .= ' ' . $deltaSymbol . ' ' . $deltaValue;
        }

        return $cell;
    }

    /**
     * Filter metrics based on threshold setting.
     *
     * @param CognitiveMetricsCollection $methods
     * @return CognitiveMetricsCollection
     */
    private function filterMetrics(CognitiveMetricsCollection $methods): CognitiveMetricsCollection
    {
        if (!$this->config->showOnlyMethodsExceedingThreshold) {
            return $methods;
        }

        $filtered = new CognitiveMetricsCollection();
        foreach ($methods as $metric) {
            if ($metric->getScore() <= $this->config->scoreThreshold) {
                continue;
            }

            $filtered->add($metric);
        }

        return $filtered;
    }

    /**
     * Build the table header row for grouped tables (without class column).
     */
    private function buildTableHeader(): string
    {
        $headers = ['Method'];

        if ($this->config->showDetailedCognitiveMetrics) {
            $headers = array_merge($headers, [
                'Lines',
                'Args',
                'Returns',
                'Variables',
                'Property Calls',
                'If',
                'If Nesting',
                'Else'
            ]);
        }

        $headers[] = 'Cognitive Complexity';

        if ($this->config->showHalsteadComplexity) {
            $headers = array_merge($headers, [
                'Halstead Volume',
                'Halstead Difficulty',
                'Halstead Effort'
            ]);
        }

        if ($this->config->showCyclomaticComplexity) {
            $headers[] = 'Cyclomatic Complexity';
        }

        return '| ' . implode(' | ', $headers) . ' |';
    }

    /**
     * Build the table header row for single table (with class column).
     */
    private function buildTableHeaderWithClass(): string
    {
        $headers = ['Class', 'Method'];

        if ($this->config->showDetailedCognitiveMetrics) {
            $headers = array_merge($headers, [
                'Lines',
                'Args',
                'Returns',
                'Variables',
                'Property Calls',
                'If',
                'If Nesting',
                'Else'
            ]);
        }

        $headers[] = 'Cognitive Complexity';

        if ($this->config->showHalsteadComplexity) {
            $headers = array_merge($headers, [
                'Halstead Volume',
                'Halstead Difficulty',
                'Halstead Effort'
            ]);
        }

        if ($this->config->showCyclomaticComplexity) {
            $headers[] = 'Cyclomatic Complexity';
        }

        return '| ' . implode(' | ', $headers) . ' |';
    }

    /**
     * Build the table separator row for grouped tables.
     */
    private function buildTableSeparator(): string
    {
        $count = 1; // Method column

        if ($this->config->showDetailedCognitiveMetrics) {
            $count += 8; // 8 detailed metrics
        }

        $count += 1; // Cognitive Complexity

        if ($this->config->showHalsteadComplexity) {
            $count += 3; // 3 Halstead metrics
        }

        if ($this->config->showCyclomaticComplexity) {
            $count += 1; // Cyclomatic complexity
        }

        return '|' . str_repeat('--------|', $count);
    }

    /**
     * Build the table separator row for single table.
     */
    private function buildTableSeparatorWithClass(): string
    {
        $count = 2; // Class + Method columns

        if ($this->config->showDetailedCognitiveMetrics) {
            $count += 8; // 8 detailed metrics
        }

        $count += 1; // Cognitive Complexity

        if ($this->config->showHalsteadComplexity) {
            $count += 3; // 3 Halstead metrics
        }

        if ($this->config->showCyclomaticComplexity) {
            $count += 1; // Cyclomatic complexity
        }

        return '|' . str_repeat('--------|', $count);
    }

    /**
     * Build a table row for grouped tables (without class column).
     */
    private function buildTableRow(CognitiveMetrics $data): string
    {
        $cells = [$this->escape($data->getMethod())];

        if ($this->config->showDetailedCognitiveMetrics) {
            $cells[] = $this->generateMetricCell($data->getLineCount(), $data->getLineCountWeight(), $data->getLineCountWeightDelta());
            $cells[] = $this->generateMetricCell($data->getArgCount(), $data->getArgCountWeight(), $data->getArgCountWeightDelta());
            $cells[] = $this->generateMetricCell($data->getReturnCount(), $data->getReturnCountWeight(), $data->getReturnCountWeightDelta());
            $cells[] = $this->generateMetricCell($data->getVariableCount(), $data->getVariableCountWeight(), $data->getVariableCountWeightDelta());
            $cells[] = $this->generateMetricCell($data->getPropertyCallCount(), $data->getPropertyCallCountWeight(), $data->getPropertyCallCountWeightDelta());
            $cells[] = $this->generateMetricCell($data->getIfCount(), $data->getIfCountWeight(), $data->getIfCountWeightDelta());
            $cells[] = $this->generateMetricCell($data->getIfNestingLevel(), $data->getIfNestingLevelWeight(), $data->getIfNestingLevelWeightDelta());
            $cells[] = $this->generateMetricCell($data->getElseCount(), $data->getElseCountWeight(), $data->getElseCountWeightDelta());
        }

        $cells[] = $this->formatNumber($data->getScore());

        $cells = $this->addHalsteadCells($cells, $data);
        $cells = $this->addCyclomaticCell($cells, $data);

        return '| ' . implode(' | ', $cells) . ' |';
    }

    /**
     * Build a table row for single table (with class column).
     */
    private function buildTableRowWithClass(CognitiveMetrics $data): string
    {
        $cells = [
            $this->escape($data->getClass()),
            $this->escape($data->getMethod())
        ];

        if ($this->config->showDetailedCognitiveMetrics) {
            $cells[] = $this->generateMetricCell($data->getLineCount(), $data->getLineCountWeight(), $data->getLineCountWeightDelta());
            $cells[] = $this->generateMetricCell($data->getArgCount(), $data->getArgCountWeight(), $data->getArgCountWeightDelta());
            $cells[] = $this->generateMetricCell($data->getReturnCount(), $data->getReturnCountWeight(), $data->getReturnCountWeightDelta());
            $cells[] = $this->generateMetricCell($data->getVariableCount(), $data->getVariableCountWeight(), $data->getVariableCountWeightDelta());
            $cells[] = $this->generateMetricCell($data->getPropertyCallCount(), $data->getPropertyCallCountWeight(), $data->getPropertyCallCountWeightDelta());
            $cells[] = $this->generateMetricCell($data->getIfCount(), $data->getIfCountWeight(), $data->getIfCountWeightDelta());
            $cells[] = $this->generateMetricCell($data->getIfNestingLevel(), $data->getIfNestingLevelWeight(), $data->getIfNestingLevelWeightDelta());
            $cells[] = $this->generateMetricCell($data->getElseCount(), $data->getElseCountWeight(), $data->getElseCountWeightDelta());
        }

        $cells[] = $this->formatNumber($data->getScore());

        $cells = $this->addHalsteadCells($cells, $data);
        $cells = $this->addCyclomaticCell($cells, $data);

        return '| ' . implode(' | ', $cells) . ' |';
    }

    /**
     * Add Halstead complexity cells to the row.
     *
     * @param array<int, string> $cells
     * @return array<int, string>
     */
    private function addHalsteadCells(array $cells, CognitiveMetrics $data): array
    {
        if (!$this->config->showHalsteadComplexity) {
            return $cells;
        }

        $halstead = $data->getHalstead();
        if ($halstead === null) {
            $cells[] = 'N/A';
            $cells[] = 'N/A';
            $cells[] = 'N/A';
            return $cells;
        }

        $cells[] = $this->formatNumber($halstead->volume);
        $cells[] = $this->formatNumber($halstead->difficulty);
        $cells[] = $this->formatNumber($halstead->effort);

        return $cells;
    }

    /**
     * Add Cyclomatic complexity cell to the row.
     *
     * @param array<int, string> $cells
     * @return array<int, string>
     */
    private function addCyclomaticCell(array $cells, CognitiveMetrics $data): array
    {
        if (!$this->config->showCyclomaticComplexity) {
            return $cells;
        }

        $cyclomatic = $data->getCyclomatic();
        if ($cyclomatic === null) {
            $cells[] = 'N/A';
            return $cells;
        }

        $cells[] = $cyclomatic->complexity . ' (' . $cyclomatic->riskLevel . ')';

        return $cells;
    }


    /**
     * Generate Markdown content using the metrics data.
     *
     * @param CognitiveMetricsCollection $metrics
     * @return string
     */
    private function generateMarkdown(CognitiveMetricsCollection $metrics): string
    {
        if ($this->config->groupByClass) {
            return $this->generateGroupedMarkdown($metrics);
        }

        return $this->generateSingleTableMarkdown($metrics);
    }

    /**
     * Generate Markdown with methods grouped by class.
     */
    private function generateGroupedMarkdown(CognitiveMetricsCollection $metrics): string
    {
        $groupedByClass = $metrics->groupBy('class');
        $datetime = (new Datetime())->format('Y-m-d H:i:s');

        $markdown = "# Cognitive Metrics Report\n\n";
        $markdown .= "**Generated:** {$datetime}\n\n";
        $markdown .= "**Total Classes:** " . count($groupedByClass) . "\n\n";

        if ($this->config->showOnlyMethodsExceedingThreshold && $this->config->scoreThreshold > 0) {
            $markdown .= "**Note:** Only showing methods exceeding threshold of " . $this->formatNumber($this->config->scoreThreshold) . "\n\n";
        }

        $markdown .= "---\n\n";

        foreach ($groupedByClass as $class => $methods) {
            $filteredMethods = $this->filterMetrics($methods);

            if (count($filteredMethods) === 0) {
                continue;
            }

            // Get file path from first method in the collection
            $firstMethod = null;
            foreach ($filteredMethods as $method) {
                $firstMethod = $method;
                break;
            }

            $markdown .= "* **Class:** " . $this->escape((string)$class) . "\n";
            if ($firstMethod !== null) {
                $markdown .= "* **File:** " . $this->escape($firstMethod->getFileName()) . "\n";
            }
            $markdown .= "\n";

            // Table header and separator
            $markdown .= $this->buildTableHeader() . "\n";
            $markdown .= $this->buildTableSeparator() . "\n";

            // Table rows
            foreach ($filteredMethods as $data) {
                $markdown .= $this->buildTableRow($data) . "\n";
            }

            $markdown .= "\n---\n\n";
        }

        return $markdown;
    }

    /**
     * Generate Markdown with all methods in a single table.
     */
    private function generateSingleTableMarkdown(CognitiveMetricsCollection $metrics): string
    {
        $datetime = (new Datetime())->format('Y-m-d H:i:s');
        $filteredMetrics = $this->filterMetrics($metrics);
        $totalMethods = count($filteredMetrics);

        $markdown = "# Cognitive Metrics Report\n\n";
        $markdown .= "**Generated:** {$datetime}\n\n";

        if ($this->config->showOnlyMethodsExceedingThreshold && $this->config->scoreThreshold > 0) {
            $markdown .= "**Note:** Only showing methods exceeding threshold of " . $this->formatNumber($this->config->scoreThreshold) . "\n\n";
        }

        $markdown .= "## All Methods ({$totalMethods} total)\n\n";

        if ($totalMethods > 0) {
            // Table header and separator
            $markdown .= $this->buildTableHeaderWithClass() . "\n";
            $markdown .= $this->buildTableSeparatorWithClass() . "\n";

            // Table rows
            foreach ($filteredMetrics as $data) {
                $markdown .= $this->buildTableRowWithClass($data) . "\n";
            }
        }

        return $markdown;
    }
}
