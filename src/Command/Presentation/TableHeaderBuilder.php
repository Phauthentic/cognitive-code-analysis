<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

/**
 * Helper class for building table headers
 */
class TableHeaderBuilder
{
    /**
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public function __construct(
        private readonly CognitiveConfig $config,
        private readonly bool $hasCoverage = false,
    ) {
    }

    /**
     * Get headers for grouped tables (without class column)
     *
     * @return array<int, string>
     */
    public function getGroupedTableHeaders(): array
    {
        $fields = [
            "Method Name",
        ];

        $fields = $this->addCognitiveMetricDetails($fields);

        $fields[] = "Cognitive\nComplexity";

        $fields = $this->addHalsteadHeaders($fields);
        $fields = $this->addCyclomaticHeaders($fields);
        $fields = $this->addCoverageHeader($fields);

        return $fields;
    }

    /**
     * @param array<int, string> $fields
     * @return array<int, string>
     */
    private function addCoverageHeader(array $fields): array
    {
        if (!$this->hasCoverage) {
            return $fields;
        }

        $fields[] = "Line\nCoverage";

        return $fields;
    }

    /**
     * Get headers for single table (with class column)
     *
     * @return array<int, string>
     */
    public function getSingleTableHeaders(): array
    {
        $fields = [
            "Class",
            "Method Name",
        ];

        $fields = $this->addCognitiveMetricDetails($fields);

        $fields[] = "Cognitive\nComplexity";

        $fields = $this->addHalsteadHeaders($fields);
        $fields = $this->addCyclomaticHeaders($fields);

        if ($this->hasCoverage) {
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

    /**
     * @param array<int, string> $fields
     * @return array<int, string>
     */
    private function addCognitiveMetricDetails(array $fields): array
    {
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
        return $fields;
    }
}
