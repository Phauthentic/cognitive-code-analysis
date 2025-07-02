<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

use Exception;
use JsonException;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\BaselineService;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRenderer;
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
    private const ARGUMENT_PATH = 'path';

    public function __construct(
        private MetricsFacade $metricsFacade,
        private CognitiveMetricTextRenderer $renderer,
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
                suggestedValues: ['json', 'csv', 'html']
            )
            ->addOption(
                name: self::OPTION_REPORT_FILE,
                shortcut: 'f',
                mode: InputArgument::OPTIONAL,
                description: 'File to write the report to.'
            )
            ->addOption(
                name: self::OPTION_DEBUG,
                mode: InputArgument::OPTIONAL,
                description: 'Enables debug output',
                default: false
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
        $path = $input->getArgument(self::ARGUMENT_PATH);

        $configFile = $input->getOption(self::OPTION_CONFIG_FILE);
        if ($configFile && !$this->loadConfiguration($configFile, $output)) {
            return Command::FAILURE;
        }

        $metricsCollection = $this->metricsFacade->getCognitiveMetrics($path);

        $this->handleBaseLine($input, $metricsCollection);
        $this->handleExport($input, $output, $metricsCollection);

        $this->renderer->render($metricsCollection, $this->metricsFacade->getConfig());

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
     * @throws CognitiveAnalysisException
     * @throws JsonException
     */
    private function handleExport(InputInterface $input, OutputInterface $output, CognitiveMetricsCollection $metricsCollection): bool
    {
        $reportType = $input->getOption(self::OPTION_REPORT_TYPE);
        $reportFile = $input->getOption(self::OPTION_REPORT_FILE);

        if (
            $this->areBothReportOptionsMissing($reportType, $reportFile)
            || $this->isOneReportOptionMissing($reportType, $reportFile, $output)
            || !$this->isValidReportType($reportType, $output)
        ) {
            return false;
        }

        $this->metricsFacade->exportMetricsReport($metricsCollection, $reportType, $reportFile);

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
}
