<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages;

use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\PipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline stage for collecting cognitive metrics.
 */
class MetricsCollectionStage extends PipelineStage
{
    public function __construct(
        private readonly MetricsFacade $metricsFacade
    ) {
    }

    public function execute(ExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();

        // Get coverage data from previous stage
        $coverageData = $context->getData('coverageReader');

        // Get metrics
        $metricsCollection = $this->metricsFacade->getCognitiveMetricsFromPaths(
            $commandContext->getPaths(),
            $coverageData
        );

        // Store metrics collection in context
        $context->setData('metricsCollection', $metricsCollection);

        // Record statistics
        $context->setStatistic('filesProcessed', count($commandContext->getPaths()));
        $context->setStatistic('metricsCollected', count($metricsCollection));

        return OperationResult::success();
    }

    public function shouldSkip(ExecutionContext $context): bool
    {
        return false; // Metrics collection should never be skipped
    }

    public function getStageName(): string
    {
        return 'MetricsCollection';
    }
}
