<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveAnalysis;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Baseline;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Handler for baseline operations in cognitive metrics command.
 * Encapsulates baseline loading and delta calculation logic.
 */
class BaselineHandler
{
    public function __construct(
        private readonly Baseline $baselineService
    ) {
    }

    /**
     * Apply baseline to the metrics collection if baseline file is provided.
     * Returns success result if no baseline file is provided or processing succeeds.
     * Returns failure result if processing fails.
     */
    public function apply(
        CognitiveMetricsCommandContext $context,
        CognitiveMetricsCollection $metricsCollection
    ): OperationResult {
        if (!$context->hasBaselineFile()) {
            return OperationResult::success();
        }

        $baselineFile = $context->getBaselineFile();
        if ($baselineFile === null) {
            return OperationResult::success();
        }

        try {
            $baseline = $this->baselineService->loadBaseline($baselineFile);
            $this->baselineService->calculateDeltas($metricsCollection, $baseline);
            return OperationResult::success();
        } catch (Exception $e) {
            return OperationResult::failure('Failed to process baseline: ' . $e->getMessage());
        }
    }
}
