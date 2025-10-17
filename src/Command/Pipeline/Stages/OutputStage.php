<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages;

use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\PipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRendererInterface;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline stage for console output rendering.
 * Handles displaying cognitive metrics to the console.
 */
class OutputStage extends PipelineStage
{
    public function __construct(
        private readonly CognitiveMetricTextRendererInterface $renderer
    ) {
    }

    public function execute(ExecutionContext $context): OperationResult
    {
        $sortedMetricsCollection = $context->getData('sortedMetricsCollection');

        if ($sortedMetricsCollection === null) {
            return OperationResult::failure('Metrics collection not available for console output.');
        }

        // Render to console
        $this->renderer->render($sortedMetricsCollection, $context->getOutput());

        // Record statistics
        $context->setStatistic('consoleOutputRendered', true);

        return OperationResult::success();
    }

    public function shouldSkip(ExecutionContext $context): bool
    {
        $commandContext = $context->getCommandContext();
        // Skip console output if report generation was requested
        return $commandContext->hasReportOptions();
    }

    public function getStageName(): string
    {
        return 'ConsoleOutput';
    }
}
