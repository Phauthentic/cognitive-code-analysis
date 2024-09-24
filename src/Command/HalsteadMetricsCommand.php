<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Command;

use Exception;
use Phauthentic\CodeQualityMetrics\Business\Halstead\Exporter\CsvExporter;
use Phauthentic\CodeQualityMetrics\Business\Halstead\Exporter\JsonExporter;
use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollection;
use Phauthentic\CodeQualityMetrics\Business\MetricsFacade;
use Phauthentic\CodeQualityMetrics\Command\Presentation\HalsteadMetricTextRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to parse PHP files or directories and output method metrics.
 */
#[AsCommand(
    name: 'metrics:halstead',
    description: 'Parse PHP files or directories and output method metrics.'
)]
class HalsteadMetricsCommand extends Command
{
    // Option names for exporting metrics in different formats and loading a configuration file.
    private const OPTION_EXPORT_JSON = 'export-json';
    private const OPTION_EXPORT_CSV = 'export-csv';
    private const OPTION_EXPORT_HTML = 'export-html';
    private const OPTION_CONFIG_FILE = 'config';

    // Argument name for the path to the PHP files or directories.
    private const ARGUMENT_PATH = 'path';

    /**
     * Constructor to initialize dependencies.
     */
    public function __construct(
        private MetricsFacade $metricsFacade,
        private HalsteadMetricTextRenderer $metricTextRenderer
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
            ->addOption(self::OPTION_EXPORT_CSV, null, InputArgument::OPTIONAL, 'Writes metrics to a CSV file', null)
            ->addOption(self::OPTION_EXPORT_JSON, null, InputArgument::OPTIONAL, 'Writes metrics to a JSON file', null)
            ->addOption(self::OPTION_EXPORT_HTML, null, InputArgument::OPTIONAL, 'Writes metrics to an HTML file', null)
            ->addOption(self::OPTION_CONFIG_FILE, null, InputArgument::OPTIONAL, 'Path to a configuration file', null);
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
        $metricsCollection = $this->metricsFacade->getHalsteadMetrics($path);

        // Render the metrics to the console.
        $this->metricTextRenderer->render($metricsCollection, $output);

        // Handle different export options.
        $this->handleExportOptions($input, $metricsCollection);

        return Command::SUCCESS;
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
     * @param HalsteadMetricsCollection $metricsCollection
     * @return void
     */
    private function handleExportOptions(InputInterface $input, HalsteadMetricsCollection $metricsCollection): void
    {
        if ($input->getOption(self::OPTION_EXPORT_JSON)) {
            $file = $input->getOption(self::OPTION_EXPORT_JSON);
            (new JsonExporter())->export($metricsCollection, $file);
        }

        if ($input->getOption(self::OPTION_EXPORT_CSV)) {
            $file = $input->getOption(self::OPTION_EXPORT_CSV);
            (new CsvExporter())->export($metricsCollection, $file);
        }
    }
}
