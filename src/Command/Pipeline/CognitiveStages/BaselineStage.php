<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Baseline;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\PipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline stage for applying baseline to metrics.
 * Encapsulates baseline loading and delta calculation logic.
 */
class BaselineStage extends PipelineStage
{
    public function __construct(
        private readonly Baseline $baselineService
    ) {
    }

    public function execute(ExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();
        $metricsCollection = $context->getData('metricsCollection');

        if ($metricsCollection === null) {
            return OperationResult::failure('Metrics collection not available for baseline.');
        }

        $baselineFile = $commandContext->getBaselineFile();
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

    public function shouldSkip(ExecutionContext $context): bool
    {
        $commandContext = $context->getCommandContext();
        return !$commandContext->hasBaselineFile();
    }

    public function getStageName(): string
    {
        return 'Baseline';
    }
}
