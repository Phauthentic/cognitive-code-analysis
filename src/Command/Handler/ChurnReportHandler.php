<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Handler;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\ChurnReportFactoryInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

class ChurnReportHandler
{
    public function __construct(
        private MetricsFacade $metricsFacade,
        private OutputInterface $output,
        private ChurnReportFactoryInterface $exporterFactory
    ) {
    }

    /**
     * Handles report option validation and report generation.
     *
     * @param array<string, array<string, mixed>> $classes
     */
    public function exportToFile(
        array $classes,
        ?string $reportType,
        ?string $reportFile,
    ): int {
        if ($this->hasIncompleteReportOptions($reportType, $reportFile)) {
            $this->output->writeln('<error>Both report type and file must be provided.</error>');

            return Command::FAILURE;
        }

        if (!$this->isValidReportType($reportType)) {
            return $this->handleInvalidReportType($reportType);
        }

        try {
            $this->metricsFacade->exportChurnReport(
                classes: $classes,
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
        return $this->exporterFactory->isSupported($reportType);
    }

    private function handleExceptions(Exception $exception): int
    {
        $this->output->writeln(sprintf(
            '<error>Error generating report: %s</error>',
            $exception->getMessage()
        ));

        return Command::FAILURE;
    }

    private function handleInvalidReportType(?string $reportType): int
    {
        $supportedTypes = implode('`, `', $this->exporterFactory->getSupportedTypes());
        $this->output->writeln(sprintf(
            '<error>Invalid report type `%s` provided. Supported types: `%s`</error>',
            $reportType,
            $supportedTypes
        ));

        return Command::FAILURE;
    }
}
