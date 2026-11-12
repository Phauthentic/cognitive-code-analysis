<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
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
        $coverageData = $context->getCoverageReader();

        // Get metrics
        $metricsCollection = $this->metricsFacade->getCognitiveMetricsFromPaths(
            $commandContext->getPaths(),
            $coverageData
        );

        // Store metrics collection in context
        $context->setData('metricsCollection', $metricsCollection);

        // Record statistics
        // Distinct files that produced metrics; the collection is keyed by class::method.
        $filesProcessed = count(array_unique(array_map(
            static fn (CognitiveMetrics $metric): string => $metric->getFileName(),
            iterator_to_array($metricsCollection, false)
        )));
        $context->setStatistic('filesProcessed', $filesProcessed);
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
