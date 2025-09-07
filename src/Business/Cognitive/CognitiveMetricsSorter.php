<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

use InvalidArgumentException;

/**
 * Service class for sorting CognitiveMetricsCollection
 */
class CognitiveMetricsSorter
{
    /**
     * Available sortable fields
     */
    private const SORTABLE_FIELDS = [
        'score',
        'halstead',
        'cyclomatic',
        'class',
        'method',
        'file',
        'lineCount',
        'argCount',
        'returnCount',
        'variableCount',
        'propertyCallCount',
        'ifCount',
        'ifNestingLevel',
        'elseCount',
        'lineCountWeight',
        'argCountWeight',
        'returnCountWeight',
        'variableCountWeight',
        'propertyCallCountWeight',
        'ifCountWeight',
        'ifNestingLevelWeight',
        'elseCountWeight'
    ];

    /**
     * Sort the metrics collection by the specified field and order
     *
     * @param CognitiveMetricsCollection $collection
     * @param string $sortBy
     * @param string $sortOrder
     * @return CognitiveMetricsCollection
     * @throws InvalidArgumentException
     */
    public function sort(
        CognitiveMetricsCollection $collection,
        string $sortBy,
        string $sortOrder = 'asc'
    ): CognitiveMetricsCollection {
        if (!in_array($sortBy, self::SORTABLE_FIELDS, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid sort field "%s". Available fields: %s',
                    $sortBy,
                    implode(', ', self::SORTABLE_FIELDS)
                )
            );
        }

        if (!in_array(strtolower($sortOrder), ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Sort order must be "asc" or "desc"');
        }

        $metrics = iterator_to_array($collection, true);

        // Convert to indexed array for sorting
        $metricsArray = array_values($metrics);

        // Sort by values
        usort($metricsArray, function (CognitiveMetrics $alpha, CognitiveMetrics $beta) use ($sortBy, $sortOrder) {
            $valueA = $this->getFieldValue($alpha, $sortBy);
            $valueB = $this->getFieldValue($beta, $sortBy);

            $comparison = $this->compareValues($valueA, $valueB);

            return strtolower($sortOrder) === 'desc' ? -$comparison : $comparison;
        });

        $sortedCollection = new CognitiveMetricsCollection();
        foreach ($metricsArray as $metric) {
            $sortedCollection->add($metric);
        }

        return $sortedCollection;
    }

    /**
     * Get the value of a field from a CognitiveMetrics object
     *
     * @param CognitiveMetrics $metrics
     * @param string $field
     * @return mixed
     */
    private function getFieldValue(CognitiveMetrics $metrics, string $field): mixed
    {
        return match ($field) {
            'score' => $metrics->getScore(),
            'halstead' => $metrics->getHalstead()?->getVolume() ?? 0.0,
            'cyclomatic' => $metrics->getCyclomatic()?->complexity ?? 0, // @phpstan-ignore-line
            'class' => $metrics->getClass(),
            'method' => $metrics->getMethod(),
            'file' => $metrics->getFileName(),
            'lineCount' => $metrics->getLineCount(),
            'argCount' => $metrics->getArgCount(),
            'returnCount' => $metrics->getReturnCount(),
            'variableCount' => $metrics->getVariableCount(),
            'propertyCallCount' => $metrics->getPropertyCallCount(),
            'ifCount' => $metrics->getIfCount(),
            'ifNestingLevel' => $metrics->getIfNestingLevel(),
            'elseCount' => $metrics->getElseCount(),
            'lineCountWeight' => $metrics->getLineCountWeight(),
            'argCountWeight' => $metrics->getArgCountWeight(),
            'returnCountWeight' => $metrics->getReturnCountWeight(),
            'variableCountWeight' => $metrics->getVariableCountWeight(),
            'propertyCallCountWeight' => $metrics->getPropertyCallCountWeight(),
            'ifCountWeight' => $metrics->getIfCountWeight(),
            'ifNestingLevelWeight' => $metrics->getIfNestingLevelWeight(),
            'elseCountWeight' => $metrics->getElseCountWeight(),
            default => throw new InvalidArgumentException("Unknown field: $field")
        };
    }

    /**
     * Compare two values for sorting
     *
     * @param mixed $alpha
     * @param mixed $beta
     * @return int
     */
    private function compareValues(mixed $alpha, mixed $beta): int
    {
        if (is_numeric($alpha) && is_numeric($beta)) {
            return $alpha <=> $beta;
        }

        if (is_string($alpha) && is_string($beta)) {
            return strcasecmp($alpha, $beta);
        }

        // Handle mixed types by converting to string
        return strcasecmp((string) $alpha, (string) $beta);
    }

    /**
     * Get all available sortable fields
     *
     * @return array<string>
     */
    public function getSortableFields(): array
    {
        return self::SORTABLE_FIELDS;
    }
}
