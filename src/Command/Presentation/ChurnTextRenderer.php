<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoberturaReader;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class ChurnTextRenderer
{
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
        'Times Changed',
        'Coverage',
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
     * containing 'score', 'churn', and 'timesChanged'.
     * @param string|null $coverageFile Path to Cobertura XML coverage file
     */
    public function renderChurnTable(array $classes, ?string $coverageFile = null): void
    {
        $coverageReader = null;
        if ($coverageFile !== null) {
            if (!file_exists($coverageFile)) {
                $this->output->writeln(sprintf(
                    '<error>Coverage file not found: %s</error>',
                    $coverageFile
                ));
                return;
            }

            try {
                $coverageReader = new CoberturaReader($coverageFile);
            } catch (\RuntimeException $e) {
                $this->output->writeln(sprintf(
                    '<error>Failed to load coverage file: %s</error>',
                    $e->getMessage()
                ));
                return;
            }
        }

        $table = new Table($this->output);
        $table->setHeaders($coverageReader !== null ? $this->churnTableHeaderWithCoverage : $this->churnTableHeader);

        foreach ($classes as $className => $data) {
            if ($data['score'] == 0 || $data['churn'] == 0) {
                continue;
            }

            $row = [
                $className,
                $data['score'],
                $data['churn'] ?? 0,
                $data['timesChanged'],
            ];

            if ($coverageReader !== null) {
                // Remove leading backslash for coverage lookup if present
                $lookupClassName = ltrim($className, '\\');
                $coverage = $coverageReader->getLineCoverage($lookupClassName);
                $row[] = $coverage !== null ? sprintf('%.2f%%', $coverage * 100) : 'N/A';
            }

            $table->addRow($row);
        }

        $table->render();
    }
}
