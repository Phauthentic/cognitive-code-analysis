<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveAnalysis;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsSorter;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Handler for sorting operations in cognitive metrics command.
 * Encapsulates sorting logic and error handling.
 */
class SortingHandler
{
    public function __construct(
        private readonly CognitiveMetricsSorter $sorter
    ) {
    }

    /**
     * Apply sorting to the metrics collection based on context options.
     * Returns success result with sorted collection if sorting succeeds or no sorting is requested.
     * Returns failure result if sorting fails.
     */
    public function sort(
        CognitiveMetricsCommandContext $context,
        CognitiveMetricsCollection $metricsCollection
    ): OperationResult {
        $sortBy = $context->getSortBy();
        $sortOrder = $context->getSortOrder();

        if ($sortBy === null) {
            return OperationResult::success($metricsCollection);
        }

        try {
            $sorted = $this->sorter->sort($metricsCollection, $sortBy, $sortOrder);
            return OperationResult::success($sorted);
        } catch (\InvalidArgumentException $e) {
            $availableFields = implode(', ', $this->sorter->getSortableFields());
            return OperationResult::failure(
                "Sorting error: {$e->getMessage()}. Available sort fields: {$availableFields}"
            );
        }
    }
}
