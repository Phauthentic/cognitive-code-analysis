<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\CoverageDataDetector;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class ChurnTextRenderer
{
    use CoverageDataDetector;

    /**
     * @var array<string>
     */
    private array $churnTableHeader = [
        'Class',
        'Score',
        'Churn',
        'Times Changed',
    ];

    /**
     * @var array<string>
     */
    private array $churnTableHeaderWithCoverage = [
        'Class',
        'Score',
        'Churn',
        'Risk Churn',
        'Times Changed',
        'Coverage',
        'Risk Level',
    ];

    public function __construct(
        private readonly OutputInterface $output
    ) {
    }

    public function reportWritten(string $reportFile): void
    {
        $this->output->writeln(sprintf(
            '<info>Report written too: %s</info>',
            $reportFile
        ));
    }

    public function renderChurnTable(ChurnMetricsCollection $metrics): void
    {
        // Determine if coverage data is available
        $hasCoverageData = $this->hasCoverageData($metrics);

        $table = new Table($this->output);
        $table->setHeaders($hasCoverageData ? $this->churnTableHeaderWithCoverage : $this->churnTableHeader);

        foreach ($metrics as $metric) {
            if ($metric->getScore() == 0 || $metric->getChurn() == 0) {
                continue;
            }

            $row = [
                $metric->getClassName(),
                $metric->getScore(),
                round($metric->getChurn(), 3),
            ];

            if ($hasCoverageData) {
                $row[] = $metric->getRiskChurn() !== null ? round($metric->getRiskChurn(), 3) : 'N/A';
            }

            $row[] = $metric->getTimesChanged();

            if ($hasCoverageData) {
                $row[] = $metric->getCoverage() !== null ? sprintf('%.2f%%', $metric->getCoverage() * 100) : 'N/A';
                $row[] = $metric->getRiskLevel() ?? 'N/A';
            }

            $table->addRow($row);
        }

        $table->render();
    }

    /**
     * Check if the metrics collection has coverage data
     */
    private function hasCoverageData(ChurnMetricsCollection $metrics): bool
    {
        foreach ($metrics as $metric) {
            if ($metric->hasCoverageData()) {
                return true;
            }
        }
        return false;
    }
}
