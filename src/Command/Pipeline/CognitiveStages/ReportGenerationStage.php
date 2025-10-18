<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\CognitiveReportFactoryInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\PipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline stage for generating cognitive metrics reports.
 * Encapsulates report validation, generation logic, and error handling.
 */
class ReportGenerationStage extends PipelineStage
{
    public function __construct(
        private readonly MetricsFacade $metricsFacade,
        private readonly CognitiveReportFactoryInterface $reportFactory
    ) {
    }

    public function execute(ExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();
        $sortedMetricsCollection = $context->getData('sortedMetricsCollection');

        if ($sortedMetricsCollection === null) {
            return OperationResult::failure('Metrics collection not available for report generation.');
        }

        $reportType = $commandContext->getReportType();
        $reportFile = $commandContext->getReportFile();

        // Validate report options
        if ($this->hasIncompleteReportOptions($reportType, $reportFile)) {
            $context->getOutput()->writeln('<error>Both report type and file must be provided.</error>');
            return OperationResult::failure('Incomplete report options provided.');
        }

        if (!$this->isValidReportType($reportType)) {
            return $this->handleInvalidReportType($context, $reportType);
        }

        try {
            $this->metricsFacade->exportMetricsReport(
                metricsCollection: $sortedMetricsCollection,
                reportType: (string)$reportType,
                filename: (string)$reportFile
            );

            // Record success statistics
            $context->setStatistic('reportGenerated', true);
            $context->setStatistic('reportType', $reportType);
            $context->setStatistic('reportFile', $reportFile);

            return OperationResult::success();
        } catch (Exception $exception) {
            return $this->handleExceptions($context, $exception);
        }
    }

    public function shouldSkip(ExecutionContext $context): bool
    {
        $commandContext = $context->getCommandContext();
        return !$commandContext->hasReportOptions();
    }

    public function getStageName(): string
    {
        return 'ReportGeneration';
    }

    private function hasIncompleteReportOptions(?string $reportType, ?string $reportFile): bool
    {
        return ($reportType === null && $reportFile !== null) || ($reportType !== null && $reportFile === null);
    }

    private function isValidReportType(?string $reportType): bool
    {
        if ($reportType === null) {
            return false;
        }
        return $this->reportFactory->isSupported($reportType);
    }

    private function handleExceptions(ExecutionContext $context, Exception $exception): OperationResult
    {
        $context->getOutput()->writeln(sprintf(
            '<error>Error generating report: %s</error>',
            $exception->getMessage()
        ));

        return OperationResult::failure('Report generation failed: ' . $exception->getMessage());
    }

    private function handleInvalidReportType(ExecutionContext $context, ?string $reportType): OperationResult
    {
        $supportedTypes = $this->reportFactory->getSupportedTypes();

        $context->getOutput()->writeln(sprintf(
            '<error>Invalid report type `%s` provided. Supported types: %s</error>',
            $reportType,
            implode(', ', $supportedTypes)
        ));

        return OperationResult::failure('Invalid report type provided.');
    }
}
