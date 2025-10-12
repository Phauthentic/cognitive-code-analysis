<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Traits\CoverageDataDetector;
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

    /**
     * @param array<string, mixed> $classes An associative array where keys are class names and values are arrays
     * containing 'score', 'churn', 'timesChanged', and optionally 'coverage', 'riskChurn', 'riskLevel'.
     */
    public function renderChurnTable(array $classes): void
    {
        // Determine if coverage data is available
        $hasCoverageData = $this->hasCoverageData($classes);

        $table = new Table($this->output);
        $table->setHeaders($hasCoverageData ? $this->churnTableHeaderWithCoverage : $this->churnTableHeader);

        foreach ($classes as $className => $data) {
            if ($data['score'] == 0 || $data['churn'] == 0) {
                continue;
            }

            $row = [
                $className,
                $data['score'],
                round($data['churn'], 3),
            ];

            if ($hasCoverageData) {
                $row[] = $data['riskChurn'] !== null ? round($data['riskChurn'], 3) : 'N/A';
            }

            $row[] = $data['timesChanged'];

            if ($hasCoverageData) {
                $row[] = $data['coverage'] !== null ? sprintf('%.2f%%', $data['coverage'] * 100) : 'N/A';
                $row[] = $data['riskLevel'] ?? 'N/A';
            }

            $table->addRow($row);
        }

        $table->render();
    }
}
