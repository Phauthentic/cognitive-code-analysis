<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveAnalysis\BaselineHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveAnalysis\ConfigurationLoadHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveAnalysis\CoverageLoadHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveAnalysis\SortingHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveMetricsReportHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRendererInterface;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CompositeCognitiveMetricsValidationSpecification;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsValidationSpecificationFactory;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CustomExporterValidation;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to parse PHP files or directories and output method metrics.
 *
 * @SuppressWarnings("CyclomaticComplexity")
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

    private CompositeCognitiveMetricsValidationSpecification $specification;

    public function __construct(
        readonly private MetricsFacade $metricsFacade,
        readonly private CognitiveMetricTextRendererInterface $renderer,
        readonly private CognitiveMetricsReportHandler $reportHandler,
        readonly private ConfigurationLoadHandler $configHandler,
        readonly private CoverageLoadHandler $coverageHandler,
        readonly private BaselineHandler $baselineHandler,
        readonly private SortingHandler $sortingHandler,
        readonly private CognitiveMetricsValidationSpecificationFactory $specificationFactory
    ) {
        parent::__construct();
        $this->specification = $this->specificationFactory->create();
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
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = new CognitiveMetricsCommandContext($input);

        // Validate all specifications
        if (!$this->specification->isSatisfiedBy($context)) {
            return $this->handleValidationError($context, $output);
        }

        // Load configuration
        $configResult = $this->configHandler->load($context);
        if ($configResult->isFailure()) {
            return $configResult->toCommandStatus($output);
        }

        // Validate custom exporters after config is loaded
        if ($context->hasReportOptions()) {
            $customExporterValidation = new CustomExporterValidation(
                $this->reportHandler->getReportFactory(),
                $this->reportHandler->getConfigService()
            );

            if (!$customExporterValidation->isSatisfiedBy($context)) {
                return $this->handleValidationError($context, $output, $customExporterValidation);
            }
        }

        // Load coverage reader
        $coverageResult = $this->coverageHandler->load($context);
        if ($coverageResult->isFailure()) {
            return $coverageResult->toCommandStatus($output);
        }

        // Get metrics
        $metricsCollection = $this->metricsFacade->getCognitiveMetricsFromPaths(
            $context->getPaths(),
            $coverageResult->getData()
        );

        // Apply baseline
        $baselineResult = $this->baselineHandler->apply($context, $metricsCollection);
        if ($baselineResult->isFailure()) {
            return $baselineResult->toCommandStatus($output);
        }

        // Apply sorting
        $sortResult = $this->sortingHandler->sort($context, $metricsCollection);
        if ($sortResult->isFailure()) {
            return $sortResult->toCommandStatus($output);
        }

        // Generate report or display results
        if ($context->hasReportOptions()) {
            return $this->reportHandler->handle(
                $sortResult->getData(),
                $context->getReportType(),
                $context->getReportFile()
            );
        }

        $this->renderer->render($sortResult->getData(), $output);

        return Command::SUCCESS;
    }


    /**
     * Handle validation errors with consistent error output.
     */
    private function handleValidationError(
        CognitiveMetricsCommandContext $context,
        OutputInterface $output,
        ?CustomExporterValidation $customExporterValidation = null
    ): int {
        $errorMessage = $customExporterValidation !== null
            ? $customExporterValidation->getErrorMessageWithContext($context)
            : $this->specification->getDetailedErrorMessage($context);

        $output->writeln('<error>' . $errorMessage . '</error>');

        return Command::FAILURE;
    }
}
