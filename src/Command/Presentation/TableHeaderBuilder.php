<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

/**
 * Helper class for building table headers
 */
class TableHeaderBuilder
{
    public function __construct(
        private readonly CognitiveConfig $config
    ) {
    }

    /**
     * Get headers for grouped tables (without class column)
     *
     * @param bool $hasCoverage Whether coverage data is available
     * @return array<int, string>
     */
    public function getGroupedTableHeaders(bool $hasCoverage = false): array
    {
        $fields = [
            "Method Name",
        ];

        if ($this->config->showDetailedCognitiveMetrics) {
            $fields = array_merge($fields, [
                "Lines",
                "Arguments",
                "Returns",
                "Variables",
                "Property\nAccesses",
                "If",
                "If Nesting\nLevel",
                "Else",
            ]);
        }

        $fields[] = "Cognitive\nComplexity";

        $fields = $this->addHalsteadHeaders($fields);
        $fields = $this->addCyclomaticHeaders($fields);

        if ($hasCoverage) {
            $fields[] = "Line\nCoverage";
        }

        return $fields;
    }

    /**
     * Get headers for single table (with class column)
     *
     * @param bool $hasCoverage Whether coverage data is available
     * @return array<int, string>
     */
    public function getSingleTableHeaders(bool $hasCoverage = false): array
    {
        $fields = [
            "Class",
            "Method Name",
        ];

        if ($this->config->showDetailedCognitiveMetrics) {
            $fields = array_merge($fields, [
                "Lines",
                "Arguments",
                "Returns",
                "Variables",
                "Property\nAccesses",
                "If",
                "If Nesting\nLevel",
                "Else",
            ]);
        }

        $fields[] = "Cognitive\nComplexity";

        $fields = $this->addHalsteadHeaders($fields);
        $fields = $this->addCyclomaticHeaders($fields);

        if ($hasCoverage) {
            $fields[] = "Coverage";
        }

        return $fields;
    }

    /**
     * Add Halstead headers to the fields array
     *
     * @param array<int, string> $fields
     * @return array<int, string>
     */
    private function addHalsteadHeaders(array $fields): array
    {
        if ($this->config->showHalsteadComplexity) {
            $fields[] = "Halstead\nVolume";
            $fields[] = "Halstead\nDifficulty";
            $fields[] = "Halstead\nEffort";
        }

        return $fields;
    }

    /**
     * Add Cyclomatic headers to the fields array
     *
     * @param array<int, string> $fields
     * @return array<int, string>
     */
    private function addCyclomaticHeaders(array $fields): array
    {
        if ($this->config->showCyclomaticComplexity) {
            $fields[] = "Cyclomatic\nComplexity";
        }

        return $fields;
    }
}
