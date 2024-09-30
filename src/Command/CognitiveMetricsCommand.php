<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Command;

use Exception;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\BaselineService;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CodeQualityMetrics\Business\MetricsFacade;
use Phauthentic\CodeQualityMetrics\Command\Presentation\CognitiveMetricTextRenderer;
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
    name: 'metrics:cognitive',
    description: 'Parse PHP files or directories and output method metrics.'
)]
class CognitiveMetricsCommand extends Command
{
    // Option names for exporting metrics in different formats and loading a configuration file.
    public const OPTION_CONFIG_FILE = 'config';
    public const OPTION_BASELINE = 'baseline';
    public const OPTION_REPORT_TYPE = 'report-type';
    public const OPTION_REPORT_FILE = 'report-file';
    public const OPTION_DEBUG = 'debug';

    // Argument name for the path to the PHP files or directories.
    private const ARGUMENT_PATH = 'path';

    /**
     * Constructor to initialize dependencies.
     */
    public function __construct(
        private MetricsFacade $metricsFacade,
        private CognitiveMetricTextRenderer $metricTextRenderer,
        private BaselineService $baselineService
    ) {
        parent::__construct();
    }

    /**
     * Configures the command options and arguments.
     */
    protected function configure(): void
    {
        $this
            ->addArgument(self::ARGUMENT_PATH, InputArgument::REQUIRED, 'Path to PHP files or directories to parse.')
            ->addOption(self::OPTION_CONFIG_FILE, 'c', InputArgument::OPTIONAL, 'Path to a configuration file', null)
            ->addOption(self::OPTION_BASELINE, 'b', InputArgument::OPTIONAL, 'Baseline file to get the delta.', null)
            ->addOption(self::OPTION_REPORT_TYPE, 'r', InputArgument::OPTIONAL, 'Type of report to generate (json, csv, html).', null, ['json', 'csv', 'html'])
            ->addOption(self::OPTION_REPORT_FILE, 'f', InputArgument::OPTIONAL, 'File to write the report to.')
            ->addOption(self::OPTION_DEBUG, null, InputArgument::OPTIONAL, 'Enables debug output', false);
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Command status code.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get the path to the files or directories to analyze.
        $path = $input->getArgument(self::ARGUMENT_PATH);

        // Load configuration if the option is provided.
        $configFile = $input->getOption(self::OPTION_CONFIG_FILE);
        if ($configFile && !$this->loadConfiguration($configFile, $output)) {
            return Command::FAILURE;
        }

        // Generate metrics for the provided path.
        $metricsCollection = $this->metricsFacade->getCognitiveMetrics($path);

        // Load baseline if the option is provided.
        $this->handleBaseLine($input, $metricsCollection);

        // Handle different export options.
        if (!$this->handleExportOptions($input, $output, $metricsCollection)) {
            return Command::FAILURE;
        }

        // Render the metrics to the console.
        $this->metricTextRenderer->render($metricsCollection, $output);

        return Command::SUCCESS;
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

    /**
     * Handles exporting metrics to different formats based on user input.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param CognitiveMetricsCollection $metricsCollection
     * @return bool
     */
    private function handleExportOptions(InputInterface $input, OutputInterface $output, CognitiveMetricsCollection $metricsCollection): bool
    {
        $reportType = $input->getOption(self::OPTION_REPORT_TYPE);
        $reportFile = $input->getOption(self::OPTION_REPORT_FILE);

        if ($this->areBothReportOptionsMissing($reportType, $reportFile)) {
            return true;
        }

        if ($this->isOneReportOptionMissing($reportType, $reportFile, $output)) {
            return false;
        }

        if (!$this->isValidReportType($reportType, $output)) {
            return false;
        }

        $this->exportMetrics($reportType, $metricsCollection, $reportFile);

        return true;
    }

    private function areBothReportOptionsMissing(?string $reportType, ?string $reportFile): bool
    {
        return $reportType === null && $reportFile === null;
    }

    private function isOneReportOptionMissing(?string $reportType, ?string $reportFile, OutputInterface $output): bool
    {
        if (($reportType !== null && $reportFile === null) || ($reportType === null && $reportFile !== null)) {
            $output->writeln('<error>Both report type and file must be provided.</error>');
            return true;
        }
        return false;
    }

    private function isValidReportType(?string $reportType, OutputInterface $output): bool
    {
        if (!in_array($reportType, ['json', 'csv', 'html'])) {
            $message = sprintf('Invalid report type `%s` provided. Only `json`, `csv`, and `html` are accepted.', $reportType);
            $output->writeln('<error>' . $message . '</error>');
            return false;
        }
        return true;
    }

    private function exportMetrics(string $reportType, CognitiveMetricsCollection $metricsCollection, string $reportFile): void
    {
        match ($reportType) {
            'json' => $this->metricsFacade->metricsCollectionToJson($metricsCollection, $reportFile),
            'csv'  => $this->metricsFacade->metricsCollectionToCsv($metricsCollection, $reportFile),
            'html' => $this->metricsFacade->metricsCollectionToHtml($metricsCollection, $reportFile),
            default => null,
        };
    }
}
