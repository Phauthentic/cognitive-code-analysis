<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsSorter;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\PipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline stage for sorting metrics collection.
 * Encapsulates sorting logic and error handling.
 */
class SortingStage extends PipelineStage
{
    public function __construct(
        private readonly CognitiveMetricsSorter $sorter
    ) {
    }

    public function execute(ExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();
        $metricsCollection = $context->getData('metricsCollection');

        $sortBy = $commandContext->getSortBy();
        $sortOrder = $commandContext->getSortOrder();

        if ($sortBy === null) {
            // Store unsorted metrics in context
            $context->setData('sortedMetricsCollection', $metricsCollection);
            return OperationResult::success();
        }

        try {
            $sorted = $this->sorter->sort($metricsCollection, $sortBy, $sortOrder);
            // Store sorted metrics in context
            $context->setData('sortedMetricsCollection', $sorted);
            return OperationResult::success();
        } catch (\InvalidArgumentException $e) {
            $availableFields = implode(', ', $this->sorter->getSortableFields());
            return OperationResult::failure(
                "Sorting error: {$e->getMessage()}. Available sort fields: {$availableFields}"
            );
        }
    }

    public function shouldSkip(ExecutionContext $context): bool
    {
        return false; // Sorting should never be skipped
    }

    public function getStageName(): string
    {
        return 'Sorting';
    }
}
