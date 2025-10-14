<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Reporter;

/**
 * Abstract base class for Markdown exporters providing common formatting utilities.
 */
abstract class AbstractMarkdownReporter
{
    /**
     * Escape special markdown characters in strings.
     *
     * @param string $string
     * @return string
     */
    protected function escapeMarkdown(string $string): string
    {
        // Escape pipe characters which would break table formatting
        // Also escape backslashes to prevent escaping issues
        return str_replace(['\\', '|'], ['\\\\', '\\|'], $string);
    }

    /**
     * Format a number to a specified number of decimal places.
     *
     * @param float $number
     * @param int $decimals
     * @return string
     */
    protected function formatNumber(float $number, int $decimals = 3): string
    {
        return number_format($number, $decimals);
    }

    /**
     * Build a markdown table header row.
     *
     * @param array<string> $headers
     * @return string
     */
    protected function buildTableHeader(array $headers): string
    {
        return '| ' . implode(' | ', $headers) . ' |';
    }

    /**
     * Build a markdown table separator row.
     *
     * @param int $columnCount Number of columns in the table
     * @return string
     */
    protected function buildTableSeparator(int $columnCount): string
    {
        return '|' . str_repeat(' --- |', $columnCount);
    }

    /**
     * Build a markdown table row from an array of cell values.
     *
     * @param array<string> $cells
     * @return string
     */
    protected function buildTableRow(array $cells): string
    {
        return '| ' . implode(' | ', $cells) . ' |';
    }

    /**
     * Format a percentage value.
     *
     * @param float $value Value between 0.0 and 1.0
     * @param int $decimals Number of decimal places
     * @return string
     */
    protected function formatPercentage(float $value, int $decimals = 2): string
    {
        return sprintf("%.{$decimals}f%%", $value * 100);
    }
}
