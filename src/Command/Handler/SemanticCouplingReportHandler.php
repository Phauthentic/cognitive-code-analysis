<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Handler;

use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\Report\SemanticCouplingReportFactory;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Handler for semantic coupling report generation.
 */
class SemanticCouplingReportHandler
{
    public function __construct(
        private readonly OutputInterface $output,
        private readonly SemanticCouplingReportFactory $reportFactory
    ) {
    }

    /**
     * Export semantic coupling collection to file.
     *
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function exportToFile(SemanticCouplingCollection $couplings, string $reportType, string $filename): int
    {
        try {
            $exporter = $this->reportFactory->create($reportType);
            $exporter->export($couplings, $filename);
            
            $this->output->writeln(sprintf(
                '<info>Semantic coupling report exported to: %s</info>',
                $filename
            ));
            
            return 0; // Success
        } catch (\Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException $e) {
            $this->output->writeln(sprintf(
                '<error>Failed to export report: %s</error>',
                $e->getMessage()
            ));
            
            return 1; // Failure
        }
    }

    /**
     * Get the report factory.
     */
    public function getReportFactory(): SemanticCouplingReportFactory
    {
        return $this->reportFactory;
    }

    /**
     * Get available report types.
     *
     * @return array<string>
     */
    public function getAvailableReportTypes(): array
    {
        return $this->reportFactory->getAvailableReportTypes();
    }

    /**
     * Check if a report type is supported.
     */
    public function isReportTypeSupported(string $reportType): bool
    {
        return $this->reportFactory->isReportTypeSupported($reportType);
    }
}
