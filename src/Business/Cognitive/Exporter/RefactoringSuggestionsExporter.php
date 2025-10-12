<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

/**
 * Exporter for generating refactoring suggestions based on cognitive metrics.
 *
 * @SuppressWarnings("PHPMD")
 */
class RefactoringSuggestionsExporter implements DataExporterInterface
{
    private RefactoringSuggestionBuilder $suggestionBuilder;

    public function __construct(
        private readonly CognitiveConfig $config
    ) {
        $this->suggestionBuilder = new RefactoringSuggestionBuilder();
    }

    /**
     * Export refactoring suggestions to a markdown file.
     *
     * @param CognitiveMetricsCollection $metrics
     * @param string $filename
     * @return void
     * @throws CognitiveAnalysisException
     */
    public function export(CognitiveMetricsCollection $metrics, string $filename): void
    {
        $markdown = $this->generateMarkdown($metrics);

        if (file_put_contents($filename, $markdown) === false) {
            throw new CognitiveAnalysisException('Could not write to file');
        }
    }

    /**
     * Generate markdown content with refactoring suggestions.
     *
     * @param CognitiveMetricsCollection $metrics
     * @return string
     */
    private function generateMarkdown(CognitiveMetricsCollection $metrics): string
    {
        $groupedByClass = $metrics->groupBy('class');
        $datetime = (new Datetime())->format('Y-m-d H:i:s');

        $totalMethods = count($metrics);
        $methodsNeedingRefactoring = $this->countMethodsNeedingRefactoring($metrics);

        $markdown = "# Refactoring Suggestions Report\n\n";
        $markdown .= "**Generated:** {$datetime}\n\n";
        $markdown .= "**Methods Analyzed:** {$totalMethods}\n";
        $markdown .= "**Methods Needing Refactoring:** {$methodsNeedingRefactoring}\n\n";

        if ($methodsNeedingRefactoring === 0) {
            return $markdown . "🎉 **Great news!** No methods exceed the configured thresholds and need refactoring.\n\n";
        }

        $markdown .= "---\n\n";

        foreach ($groupedByClass as $class => $methods) {
            $classSuggestions = $this->generateClassSuggestions((string) $class, $methods);

            if (empty($classSuggestions)) {
                continue;
            }

            $markdown .= $classSuggestions;
        }

        return $markdown;
    }

    /**
     * Generate suggestions for a specific class.
     *
     * @param string $class
     * @param CognitiveMetricsCollection $methods
     * @return string
     */
    private function generateClassSuggestions(string $class, CognitiveMetricsCollection $methods): string
    {
        $markdown = "## Class: {$class}\n\n";

        // Get file path from first method
        $firstMethod = null;
        foreach ($methods as $method) {
            $firstMethod = $method;
            break;
        }

        if ($firstMethod !== null) {
            $markdown .= "**File:** {$firstMethod->getFileName()}\n\n";
        }

        foreach ($methods as $method) {
            $methodSuggestions = $this->generateMethodSuggestions($method);

            if (empty($methodSuggestions)) {
                continue;
            }

            $markdown .= $methodSuggestions;
        }

        return $markdown;
    }

    /**
     * Generate suggestions for a specific method.
     *
     * @param CognitiveMetrics $method
     * @return string
     */
    private function generateMethodSuggestions(CognitiveMetrics $method): string
    {
        $suggestionData = $this->collectSuggestionsForMethod($method);

        if (empty($suggestionData['suggestions'])) {
            return '';
        }

        return $this->formatMethodSuggestions($method, $suggestionData);
    }

    /**
     * Collect all suggestions for a method.
     *
     * @param CognitiveMetrics $method
     * @return array{suggestions: array<RefactoringSuggestion>, exceededMetrics: array<array{name: string, value: float|int, threshold: float|int}>}
     */
    private function collectSuggestionsForMethod(CognitiveMetrics $method): array
    {
        $allSuggestions = [];
        $exceededMetrics = [];

        $this->collectCognitiveMetricSuggestions($method, $allSuggestions, $exceededMetrics);
        $this->collectCyclomaticComplexitySuggestions($method, $allSuggestions);
        $this->collectHalsteadEffortSuggestions($method, $allSuggestions);

        // Sort all suggestions by priority (highest first)
        usort($allSuggestions, fn(RefactoringSuggestion $a, RefactoringSuggestion $b) => $b->priority <=> $a->priority);

        return [
            'suggestions' => $allSuggestions,
            'exceededMetrics' => $exceededMetrics
        ];
    }

    /**
     * Collect suggestions from cognitive metrics.
     *
     * @param CognitiveMetrics $method
     * @param array<RefactoringSuggestion> $allSuggestions
     * @param array<array{name: string, value: float|int, threshold: float|int}> $exceededMetrics
     */
    private function collectCognitiveMetricSuggestions(
        CognitiveMetrics $method,
        array &$allSuggestions,
        array &$exceededMetrics
    ): void {
        $metricConfigs = $this->config->metrics;

        foreach ($metricConfigs as $metricName => $config) {
            if (!$config->enabled) {
                continue;
            }

            $value = $this->getMetricValue($method, (string) $metricName);

            if ($value <= $config->threshold) {
                continue;
            }

            $exceededMetrics[] = [
                'name' => (string) $metricName,
                'value' => $value,
                'threshold' => $config->threshold
            ];

            $suggestions = $this->suggestionBuilder->buildSuggestionsForMetric(
                (string) $metricName,
                $value,
                $config->threshold
            );
            $allSuggestions = array_merge($allSuggestions, $suggestions);
        }
    }

    /**
     * Collect cyclomatic complexity suggestions.
     *
     * @param CognitiveMetrics $method
     * @param array<RefactoringSuggestion> $allSuggestions
     */
    private function collectCyclomaticComplexitySuggestions(CognitiveMetrics $method, array &$allSuggestions): void
    {
        $cyclomatic = $method->getCyclomatic();
        if ($cyclomatic === null || $cyclomatic->complexity < 11) {
            return;
        }

        $suggestions = $this->suggestionBuilder->buildCyclomaticComplexitySuggestions($cyclomatic->complexity);
        $allSuggestions = array_merge($allSuggestions, $suggestions);
    }

    /**
     * Collect Halstead effort suggestions.
     *
     * @param CognitiveMetrics $method
     * @param array<RefactoringSuggestion> $allSuggestions
     */
    private function collectHalsteadEffortSuggestions(CognitiveMetrics $method, array &$allSuggestions): void
    {
        $halstead = $method->getHalstead();
        if ($halstead === null || $halstead->effort < 10000) {
            return;
        }

        $suggestions = $this->suggestionBuilder->buildHalsteadEffortSuggestions($halstead->effort);
        $allSuggestions = array_merge($allSuggestions, $suggestions);
    }

    /**
     * Format method suggestions into markdown.
     *
     * @param CognitiveMetrics $method
     * @param array{suggestions: array<RefactoringSuggestion>, exceededMetrics: array<array{name: string, value: float|int, threshold: float|int}>} $suggestionData
     * @return string
     */
    private function formatMethodSuggestions(CognitiveMetrics $method, array $suggestionData): string
    {
        $markdown = "### Method: {$method->getMethod()} (Line {$method->getLine()})\n\n";

        $markdown .= $this->formatMetricsSummary($method, $suggestionData['exceededMetrics']);
        $markdown .= $this->formatRefactoringSuggestions($suggestionData['suggestions']);
        $markdown .= "\n---\n\n";

        return $markdown;
    }

    /**
     * Format metrics summary section.
     *
     * @param CognitiveMetrics $method
     * @param array<array{name: string, value: float|int, threshold: float|int}> $exceededMetrics
     * @return string
     */
    private function formatMetricsSummary(CognitiveMetrics $method, array $exceededMetrics): string
    {
        $markdown = "**Metrics Summary:**\n";

        foreach ($exceededMetrics as $metric) {
            $markdown .= "- " . ucfirst(str_replace('Count', ' Count', (string) $metric['name'])) . ": {$metric['value']} (threshold: {$metric['threshold']}) ⚠️\n";
        }

        $this->addCyclomaticComplexityToSummary($method, $markdown);
        $this->addHalsteadEffortToSummary($method, $markdown);

        return $markdown . "- Cognitive Complexity: " . number_format($method->getScore(), 3) . "\n\n";
    }

    /**
     * Add cyclomatic complexity to metrics summary if applicable.
     *
     * @param CognitiveMetrics $method
     * @param string $markdown
     */
    private function addCyclomaticComplexityToSummary(CognitiveMetrics $method, string &$markdown): void
    {
        $cyclomatic = $method->getCyclomatic();
        if ($cyclomatic === null || $cyclomatic->complexity < 11) {
            return;
        }

        $markdown .= "- Cyclomatic Complexity: {$cyclomatic->complexity} ({$cyclomatic->riskLevel}) ⚠️\n";
    }

    /**
     * Add Halstead effort to metrics summary if applicable.
     *
     * @param CognitiveMetrics $method
     * @param string $markdown
     */
    private function addHalsteadEffortToSummary(CognitiveMetrics $method, string &$markdown): void
    {
        $halstead = $method->getHalstead();
        if ($halstead === null || $halstead->effort < 10000) {
            return;
        }

        $markdown .= "- Halstead Effort: " . number_format($halstead->effort, 0) . " ⚠️\n";
    }

    /**
     * Format refactoring suggestions section.
     *
     * @param array<RefactoringSuggestion> $suggestions
     * @return string
     */
    private function formatRefactoringSuggestions(array $suggestions): string
    {
        $markdown = "**Suggested Refactorings (ranked by priority):**\n\n";

        foreach ($suggestions as $suggestion) {
            $markdown .= $this->formatSuggestion($suggestion);
        }

        return $markdown;
    }

    /**
     * Format a single refactoring suggestion.
     *
     * @param RefactoringSuggestion $suggestion
     * @return string
     */
    private function formatSuggestion(RefactoringSuggestion $suggestion): string
    {
        $emoji = $suggestion->getPriorityEmoji();
        $label = $suggestion->getPriorityLabel();

        $markdown = "#### {$emoji} Priority {$suggestion->priority}: {$suggestion->technique}\n";
        $markdown .= "**Reason:** {$suggestion->getReason()}\n\n";
        $markdown .= "**Technique:** {$suggestion->description}\n\n";
        $markdown .= "**Example:**\n";
        $markdown .= $suggestion->codeExample . "\n\n";

        return $markdown;
    }

    /**
     * Get the value for a specific metric from a CognitiveMetrics object.
     *
     * @param CognitiveMetrics $method
     * @param string $metricName
     * @return float
     */
    private function getMetricValue(CognitiveMetrics $method, string $metricName): float
    {
        return match ($metricName) {
            'lineCount' => (float) $method->getLineCount(),
            'argCount' => (float) $method->getArgCount(),
            'returnCount' => (float) $method->getReturnCount(),
            'variableCount' => (float) $method->getVariableCount(),
            'propertyCallCount' => (float) $method->getPropertyCallCount(),
            'ifCount' => (float) $method->getIfCount(),
            'ifNestingLevel' => (float) $method->getIfNestingLevel(),
            'elseCount' => (float) $method->getElseCount(),
            default => 0.0,
        };
    }

    /**
     * Count how many methods need refactoring.
     *
     * @param CognitiveMetricsCollection $metrics
     * @return int
     */
    private function countMethodsNeedingRefactoring(CognitiveMetricsCollection $metrics): int
    {
        $count = 0;
        $metricConfigs = $this->config->metrics;

        foreach ($metrics as $method) {
            $needsRefactoring = false;

            // Check each metric
            foreach ($metricConfigs as $metricName => $config) {
                if (!$config->enabled) {
                    continue;
                }

                $value = $this->getMetricValue($method, (string) $metricName);
                if ($value > $config->threshold) {
                    $needsRefactoring = true;
                    break;
                }
            }

            // Check cyclomatic complexity
            if (!$needsRefactoring) {
                $cyclomatic = $method->getCyclomatic();
                if ($cyclomatic !== null && $cyclomatic->complexity >= 11) {
                    $needsRefactoring = true;
                }
            }

            // Check Halstead effort
            if (!$needsRefactoring) {
                $halstead = $method->getHalstead();
                if ($halstead !== null && $halstead->effort >= 10000) {
                    $needsRefactoring = true;
                }
            }

            if (!$needsRefactoring) {
                continue;
            }

            $count++;
        }

        return $count;
    }
}
