<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CodeCoverageFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoverageReportReaderInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Baseline;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsSorter;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveMetricsReportHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRendererInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to parse PHP files or directories and output method metrics.
 */
#[AsCommand(
    name: 'analyse',
    description: 'Parse PHP files or directories and output method metrics.'
)]
class CognitiveMetricsCommand extends Command
{
    public const OPTION_CONFIG_FILE = 'config';
    public const OPTION_BASELINE = 'baseline';
    public const OPTION_REPORT_TYPE = 'report-type';
    public const OPTION_REPORT_FILE = 'report-file';
    public const OPTION_DEBUG = 'debug';
    public const OPTION_SORT_BY = 'sort-by';
    public const OPTION_SORT_ORDER = 'sort-order';
    public const OPTION_COVERAGE_COBERTURA = 'coverage-cobertura';
    public const OPTION_COVERAGE_CLOVER = 'coverage-clover';
    private const ARGUMENT_PATH = 'path';

    public function __construct(
        readonly private MetricsFacade $metricsFacade,
        readonly private CognitiveMetricTextRendererInterface $renderer,
        readonly private Baseline $baselineService,
        readonly private CognitiveMetricsReportHandler $reportHandler,
        readonly private CognitiveMetricsSorter $sorter,
        readonly private CodeCoverageFactory $coverageFactory
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
                description: 'Path to PHP files or directories to parse. Can be a single path or comma-separated list of paths.'
            )
            ->addOption(
                name: self::OPTION_CONFIG_FILE,
                shortcut: 'c',
                mode: InputArgument::OPTIONAL,
                description: 'Path to a configuration file',
            )
            ->addOption(
                name: self::OPTION_BASELINE,
                shortcut: 'b',
                mode: InputArgument::OPTIONAL,
                description: 'Baseline file to get the delta.',
            )
            ->addOption(
                name: self::OPTION_REPORT_TYPE,
                shortcut: 'r',
                mode: InputArgument::OPTIONAL,
                description: 'Type of report to generate (json, csv, html).',
            )
            ->addOption(
                name: self::OPTION_REPORT_FILE,
                shortcut: 'f',
                mode: InputArgument::OPTIONAL,
                description: 'File to write the report to.'
            )
            ->addOption(
                name: self::OPTION_SORT_BY,
                shortcut: 's',
                mode: InputArgument::OPTIONAL,
                description: 'Field to sort by (e.g., score, halstead, cyclomatic, class, method, etc.).',
            )
            ->addOption(
                name: self::OPTION_SORT_ORDER,
                mode: InputArgument::OPTIONAL,
                description: 'Sort order: asc or desc (default: asc).',
                default: 'asc'
            )
            ->addOption(
                name: self::OPTION_DEBUG,
                mode: InputArgument::OPTIONAL,
                description: 'Enables debug output',
                default: false
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
            );
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Command status code.
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pathInput = $input->getArgument(self::ARGUMENT_PATH);
        $paths = $this->parsePaths($pathInput);

        $configFile = $input->getOption(self::OPTION_CONFIG_FILE);
        if ($configFile && !$this->loadConfiguration($configFile, $output)) {
            return Command::FAILURE;
        }

        $coverageReader = $this->handleCoverageOptions($input, $output);
        if ($coverageReader === false) {
            return Command::FAILURE;
        }

        $metricsCollection = $this->metricsFacade->getCognitiveMetricsFromPaths($paths, $coverageReader);

        $this->handleBaseLine($input, $metricsCollection);

        $sortResult = $this->applySorting($input, $output, $metricsCollection);
        if ($sortResult['status'] === Command::FAILURE) {
            return Command::FAILURE;
        }
        $metricsCollection = $sortResult['collection'];

        $reportType = $input->getOption(self::OPTION_REPORT_TYPE);
        $reportFile = $input->getOption(self::OPTION_REPORT_FILE);

        if ($reportType !== null || $reportFile !== null) {
            return $this->reportHandler->handle($metricsCollection, $reportType, $reportFile);
        }

        $this->renderer->render($metricsCollection, $output);

        return Command::SUCCESS;
    }

    /**
     * Parses the path input to handle both single paths and comma-separated multiple paths.
     *
     * @param string $pathInput The input path(s) from the command argument
     * @return array<string> Array of paths to process
     */
    private function parsePaths(string $pathInput): array
    {
        $paths = array_map('trim', explode(',', $pathInput));
        return array_filter($paths, function ($path) {
            return !empty($path);
        });
    }

    /**
     * Handles the baseline option and loads the baseline file if provided.
     *
     * @param InputInterface $input
     * @param CognitiveMetricsCollection $metricsCollection
     * @throws Exception
     */
    private function handleBaseLine(InputInterface $input, CognitiveMetricsCollection $metricsCollection): void
    {
        $baselineFile = $input->getOption(self::OPTION_BASELINE);
        if (!$baselineFile) {
            return;
        }

        $baseline = $this->baselineService->loadBaseline($baselineFile);
        $this->baselineService->calculateDeltas($metricsCollection, $baseline);
    }

    /**
     * Handle coverage options and return coverage reader
     *
     * @return CoverageReportReaderInterface|null|false Returns reader, null if no coverage, or false on error
     */
    private function handleCoverageOptions(
        InputInterface $input,
        OutputInterface $output
    ): CoverageReportReaderInterface|null|false {
        $coberturaFile = $input->getOption(self::OPTION_COVERAGE_COBERTURA);
        $cloverFile = $input->getOption(self::OPTION_COVERAGE_CLOVER);

        // Validate that only one coverage option is specified
        if ($coberturaFile !== null && $cloverFile !== null) {
            $output->writeln('<error>Only one coverage format can be specified at a time.</error>');
            return false;
        }

        $coverageFile = $coberturaFile ?? $cloverFile;
        $coverageFormat = $coberturaFile !== null ? 'cobertura' : ($cloverFile !== null ? 'clover' : null);

        if (!$this->coverageFileExists($coverageFile, $output)) {
            return false;
        }

        return $this->loadCoverageReader($coverageFile, $coverageFormat, $output);
    }

    /**
     * Apply sorting to metrics collection
     *
     * @return array{status: int, collection: CognitiveMetricsCollection}
     */
    private function applySorting(
        InputInterface $input,
        OutputInterface $output,
        CognitiveMetricsCollection $metricsCollection
    ): array {
        $sortBy = $input->getOption(self::OPTION_SORT_BY);
        $sortOrder = $input->getOption(self::OPTION_SORT_ORDER);

        if ($sortBy === null) {
            return ['status' => Command::SUCCESS, 'collection' => $metricsCollection];
        }

        try {
            $sorted = $this->sorter->sort($metricsCollection, $sortBy, $sortOrder);
            return ['status' => Command::SUCCESS, 'collection' => $sorted];
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>Sorting error: ' . $e->getMessage() . '</error>');
            $output->writeln('<info>Available sort fields: ' . implode(', ', $this->sorter->getSortableFields()) . '</info>');
            return ['status' => Command::FAILURE, 'collection' => $metricsCollection];
        }
    }

    /**
     * Loads configuration and handles errors.
     *
     * @param string $configFile
     * @param OutputInterface $output
     * @return bool Success or failure.
     */
    private function loadConfiguration(string $configFile, OutputInterface $output): bool
    {
        try {
            $this->metricsFacade->loadConfig($configFile);
            return true;
        } catch (Exception $e) {
            $output->writeln('<error>Failed to load configuration: ' . $e->getMessage() . '</error>');
            return false;
        }
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
            return $this->coverageFactory->createFromName($format, $coverageFile);
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
