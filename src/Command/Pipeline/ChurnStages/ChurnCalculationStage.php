<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnStages;

use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnPipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline stage for calculating churn metrics.
 */
class ChurnCalculationStage implements ChurnPipelineStage
{
    public function __construct(
        private readonly MetricsFacade $metricsFacade
    ) {
    }

    public function execute(ChurnExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();

        // Get coverage data from previous stage
        $coverageReader = $context->getCoverageReader();

        // Calculate churn metrics
        try {
            $metrics = $this->metricsFacade->calculateChurn(
                path: $commandContext->getPath(),
                vcsType: $commandContext->getVcsType(),
                since: $commandContext->getSince(),
                coverageReader: $coverageReader
            );
        } catch (CognitiveAnalysisException $e) {
            // The command prints the failure message, so it is not written to the output here.
            return OperationResult::failure('Churn calculation failed: ' . $e->getMessage());
        }

        // Store metrics in context
        $context->setData('churnMetrics', $metrics);

        // Record statistics
        $context->setStatistic('churnCalculated', true);
        $context->setStatistic('path', $commandContext->getPath());

        return OperationResult::success();
    }

    public function getStageName(): string
    {
        return 'ChurnCalculation';
    }

    public function shouldSkip(ChurnExecutionContext $context): bool
    {
        return false; // Churn calculation should never be skipped
    }
}
