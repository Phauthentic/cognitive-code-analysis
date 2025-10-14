<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveAnalysis;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveMetricsReportHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRendererInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Handler for output operations in cognitive metrics command.
 * Encapsulates logic for generating reports or rendering console output.
 */
class OutputHandler
{
    public function __construct(
        private readonly CognitiveMetricsReportHandler $reportHandler,
        private readonly CognitiveMetricTextRendererInterface $renderer
    ) {
    }

    /**
     * Handle output based on context options.
     * Generates report if report options are provided, otherwise renders to console.
     * Returns appropriate command status code.
     */
    public function handle(
        CognitiveMetricsCollection $collection,
        CognitiveMetricsCommandContext $context,
        OutputInterface $output
    ): int {
        if ($context->hasReportOptions()) {
            return $this->reportHandler->handle(
                $collection,
                $context->getReportType(),
                $context->getReportFile()
            );
        }

        $this->renderer->render($collection, $output);
        return Command::SUCCESS;
    }
}
