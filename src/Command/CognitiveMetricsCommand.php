<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Baseline;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsSorter;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveMetricsReportHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRendererInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
    public const OPTION_CLEAR_CACHE = 'clear-cache';
    public const OPTION_NO_CACHE = 'no-cache';
    public const OPTION_CACHE_DIR = 'cache-dir';
    private const ARGUMENT_PATH = 'path';

    public function __construct(
        readonly private MetricsFacade $metricsFacade,
        readonly private CognitiveMetricTextRendererInterface $renderer,
        readonly private Baseline $baselineService,
        readonly private CognitiveMetricsReportHandler $reportHandler,
        readonly private CognitiveMetricsSorter $sorter
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
                name: self::OPTION_CLEAR_CACHE,
                mode: InputOption::VALUE_NONE,
                description: 'Clear all cached analysis results'
            )
            ->addOption(
                name: self::OPTION_NO_CACHE,
                mode: InputOption::VALUE_NONE,
                description: 'Disable caching for this run'
            )
            ->addOption(
                name: self::OPTION_CACHE_DIR,
                mode: InputArgument::OPTIONAL,
                description: 'Override default cache directory',
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

        // Handle cache options
        if ($input->getOption(self::OPTION_CLEAR_CACHE)) {
            try {
                $this->metricsFacade->clearCache();
                $output->writeln('<info>Cache cleared successfully.</info>');
            } catch (Exception $e) {
                $output->writeln('<error>Failed to clear cache: ' . $e->getMessage() . '</error>');
            }
            return Command::SUCCESS;
        }

        $configFile = $input->getOption(self::OPTION_CONFIG_FILE);
        if ($configFile && !$this->loadConfiguration($configFile, $output)) {
            return Command::FAILURE;
        }

        // Handle cache directory override
        $cacheDir = $input->getOption(self::OPTION_CACHE_DIR);
        if ($cacheDir && $this->metricsFacade->getConfig()->cache !== null) {
            $this->metricsFacade->getConfig()->cache->directory = $cacheDir;
        }

        // Handle no-cache option
        $noCache = $input->getOption(self::OPTION_NO_CACHE);
        if ($noCache && $this->metricsFacade->getConfig()->cache !== null) {
            $this->metricsFacade->getConfig()->cache->enabled = false;
        }

        $metricsCollection = $this->metricsFacade->getCognitiveMetricsFromPaths($paths);

        $this->handleBaseLine($input, $metricsCollection);

        // Apply sorting if specified
        $sortBy = $input->getOption(self::OPTION_SORT_BY);
        $sortOrder = $input->getOption(self::OPTION_SORT_ORDER);

        if ($sortBy !== null) {
            try {
                $metricsCollection = $this->sorter->sort($metricsCollection, $sortBy, $sortOrder);
            } catch (\InvalidArgumentException $e) {
                $output->writeln('<error>Sorting error: ' . $e->getMessage() . '</error>');
                $output->writeln('<info>Available sort fields: ' . implode(', ', $this->sorter->getSortableFields()) . '</info>');
                return Command::FAILURE;
            }
        }

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
        if ($baselineFile) {
            $baseline = $this->baselineService->loadBaseline($baselineFile);
            $this->baselineService->calculateDeltas($metricsCollection, $baseline);
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
}
