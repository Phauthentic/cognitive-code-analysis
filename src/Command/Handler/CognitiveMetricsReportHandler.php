<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Handler;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\CognitiveReportFactoryInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

class CognitiveMetricsReportHandler
{
    public function __construct(
        private MetricsFacade $metricsFacade,
        private OutputInterface $output,
        private CognitiveReportFactoryInterface $reportFactory
    ) {
    }

    /**
     * Handles report option validation and report generation.
     */
    public function handle(
        CognitiveMetricsCollection $metricsCollection,
        ?string $reportType,
        ?string $reportFile,
    ): int {
        if ($this->hasIncompleteReportOptions($reportType, $reportFile)) {
            $this->output->writeln('<error>Both report type and file must be provided.</error>');

            return Command::FAILURE;
        }

        if (!$this->isValidReportType($reportType)) {
            return $this->handleInvalidReporType($reportType);
        }

        try {
            $this->metricsFacade->exportMetricsReport(
                metricsCollection: $metricsCollection,
                reportType: (string)$reportType,
                filename: (string)$reportFile
            );

            return Command::SUCCESS;
        } catch (Exception $exception) {
            return $this->handleExceptions($exception);
        }
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

    private function handleExceptions(Exception $exception): int
    {
        $this->output->writeln(sprintf(
            '<error>Error generating report: %s</error>',
            $exception->getMessage()
        ));

        return Command::FAILURE;
    }

    public function handleInvalidReporType(?string $reportType): int
    {
        $supportedTypes = $this->reportFactory->getSupportedTypes();

        $this->output->writeln(sprintf(
            '<error>Invalid report type `%s` provided. Supported types: %s</error>',
            $reportType,
            implode(', ', $supportedTypes)
        ));

        return Command::FAILURE;
    }
}
