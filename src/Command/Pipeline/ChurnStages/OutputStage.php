<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnStages;

use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnPipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\ChurnTextRenderer;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline stage for console output rendering of churn metrics.
 */
class OutputStage implements ChurnPipelineStage
{
    public function __construct(
        private readonly ChurnTextRenderer $renderer
    ) {
    }

    public function execute(ChurnExecutionContext $context): OperationResult
    {
        $churnMetrics = $context->getData('churnMetrics');

        if ($churnMetrics === null) {
            return OperationResult::failure('Churn metrics not available for console output.');
        }

        // Render to console
        $this->renderer->renderChurnTable(metrics: $churnMetrics);

        // Record statistics
        $context->setStatistic('consoleOutputRendered', true);

        return OperationResult::success();
    }

    public function shouldSkip(ChurnExecutionContext $context): bool
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
