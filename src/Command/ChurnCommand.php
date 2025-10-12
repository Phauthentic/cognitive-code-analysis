<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CloverReader;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoberturaReader;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoverageReportReaderInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\ChurnReportHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\ChurnTextRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'churn',
    description: 'Calculates the churn based on version control history.',
)]
class ChurnCommand extends Command
{
    private const ARGUMENT_PATH = 'path';
    public const OPTION_CONFIG_FILE = 'config';
    public const OPTION_VCS = 'vcs';
    public const OPTION_SINCE = 'since';
    public const OPTION_DEBUG = 'debug';
    public const OPTION_REPORT_TYPE = 'report-type';
    public const OPTION_REPORT_FILE = 'report-file';
    public const OPTION_COVERAGE_COBERTURA = 'coverage-cobertura';
    public const OPTION_COVERAGE_CLOVER = 'coverage-clover';

    /**
     * Constructor to initialize dependencies.
     */
    public function __construct(
        readonly private MetricsFacade $metricsFacade,
        readonly private ChurnTextRenderer $renderer,
        readonly private ChurnReportHandler $report
    ) {
        parent::__construct();
    }

    /**
     * Configures the command options and arguments.
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                name: self::ARGUMENT_PATH,
                mode: InputArgument::REQUIRED,
                description: 'Path to PHP files or directories to parse.'
            )
            ->addOption(
                name: self::OPTION_CONFIG_FILE,
                shortcut: 'c',
                mode: InputArgument::OPTIONAL,
                description: 'Path to a configuration file',
            )
            ->addOption(
                name: self::OPTION_SINCE,
                shortcut: 's',
                mode: InputArgument::OPTIONAL,
                description: 'Where to start counting changes from',
                default: '2000-01-01'
            )
            ->addOption(
                name: self::OPTION_VCS,
                mode: InputArgument::OPTIONAL,
                description: 'Path to a configuration file',
                default: 'git'
            )
            ->addOption(
                name: self::OPTION_DEBUG,
                mode: InputArgument::OPTIONAL,
                description: 'Enables debug output',
                default: false
            )
            ->addOption(
                name: self::OPTION_REPORT_TYPE,
                shortcut: 'r',
                mode: InputArgument::OPTIONAL,
                description: 'Type of report to generate (json, html, csv, svg-treemap, markdown).',
            )
            ->addOption(
                name: self::OPTION_REPORT_FILE,
                shortcut: 'f',
                mode: InputArgument::OPTIONAL,
                description: 'File to write the report to.'
            )
            ->addOption(
                name: self::OPTION_COVERAGE_COBERTURA,
                mode: InputArgument::OPTIONAL,
                description: 'Path to Cobertura XML coverage file to display coverage data.'
            )
            ->addOption(
                name: self::OPTION_COVERAGE_CLOVER,
                mode: InputArgument::OPTIONAL,
                description: 'Path to Clover XML coverage file to display coverage data.'
            )
        ;
    }

    /**
     * Executes the command.
     *
     * @SuppressWarnings("UnusedFormalParameter")
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Command status code.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $coberturaFile = $input->getOption(self::OPTION_COVERAGE_COBERTURA);
        $cloverFile = $input->getOption(self::OPTION_COVERAGE_CLOVER);

        // Validate that only one coverage option is specified
        if ($coberturaFile !== null && $cloverFile !== null) {
            $output->writeln('<error>Only one coverage format can be specified at a time.</error>');
            return self::FAILURE;
        }

        $coverageFile = $coberturaFile ?? $cloverFile;
        $coverageFormat = $coberturaFile !== null ? 'cobertura' : ($cloverFile !== null ? 'clover' : null);

        if (!$this->coverageFileExists($coverageFile, $output)) {
            return self::FAILURE;
        }

        $coverageReader = $this->loadCoverageReader($coverageFile, $coverageFormat, $output);
        if ($coverageReader === false) {
            return self::FAILURE;
        }

        $classes = $this->metricsFacade->calculateChurn(
            path: $input->getArgument(self::ARGUMENT_PATH),
            vcsType: $input->getOption(self::OPTION_VCS),
            since: $input->getOption(self::OPTION_SINCE),
            coverageReader: $coverageReader
        );

        $reportType = $input->getOption(self::OPTION_REPORT_TYPE);
        $reportFile = $input->getOption(self::OPTION_REPORT_FILE);

        if ($reportType !== null || $reportFile !== null) {
            return $this->report->exportToFile($classes, $reportType, $reportFile);
        }

        $this->renderer->renderChurnTable(
            classes: $classes
        );

        return self::SUCCESS;
    }

    /**
     * Load coverage reader from file
     *
     * @param string|null $coverageFile Path to coverage file or null
     * @param string|null $format Coverage format ('cobertura', 'clover') or null for auto-detect
     * @param OutputInterface $output Output interface for error messages
     * @return CoverageReportReaderInterface|null|false Returns reader instance, null if no file provided, or false on error
     */
    private function loadCoverageReader(
        ?string $coverageFile,
        ?string $format,
        OutputInterface $output
    ): CoverageReportReaderInterface|null|false {
        if ($coverageFile === null) {
            return null;
        }

        // Auto-detect format if not specified
        if ($format === null) {
            $format = $this->detectCoverageFormat($coverageFile);
            if ($format === null) {
                $output->writeln('<error>Unable to detect coverage file format. Please specify format explicitly.</error>');
                return false;
            }
        }

        try {
            return match ($format) {
                'cobertura' => new CoberturaReader($coverageFile),
                'clover' => new CloverReader($coverageFile),
                default => throw new CognitiveAnalysisException("Unsupported coverage format: {$format}"),
            };
        } catch (CognitiveAnalysisException $e) {
            $output->writeln(sprintf(
                '<error>Failed to load coverage file: %s</error>',
                $e->getMessage()
            ));
            return false;
        }
    }

    /**
     * Detect coverage file format by examining the XML structure
     */
    private function detectCoverageFormat(string $coverageFile): ?string
    {
        $content = file_get_contents($coverageFile);
        if ($content === false) {
            return null;
        }

        // Cobertura format has <coverage> root element with line-rate attribute
        if (preg_match('/<coverage[^>]*line-rate=/', $content)) {
            return 'cobertura';
        }

        // Clover format has <coverage> with generated attribute and <project> child
        if (preg_match('/<coverage[^>]*generated=.*<project/', $content)) {
            return 'clover';
        }

        return null;
    }

    private function coverageFileExists(?string $coverageFile, OutputInterface $output): bool
    {
        // If no coverage file is provided, validation passes (backward compatibility)
        if ($coverageFile === null) {
            return true;
        }

        // If coverage file is provided, check if it exists
        if (file_exists($coverageFile)) {
            return true;
        }

        // Coverage file was provided but doesn't exist - show error
        $output->writeln(sprintf(
            '<error>Coverage file not found: %s</error>',
            $coverageFile
        ));

        return false;
    }
}
