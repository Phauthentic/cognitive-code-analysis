<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CloverReader;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoberturaReader;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoverageReportReaderInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\ChurnReportHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\ChurnTextRenderer;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications\ChurnCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications\CompositeChurnValidationSpecification;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications\ChurnValidationSpecificationFactory;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications\CustomExporterValidationSpecification;
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

    private CompositeChurnValidationSpecification $validationSpecification;

    /**
     * Constructor to initialize dependencies.
     */
    public function __construct(
        readonly private MetricsFacade $metricsFacade,
        readonly private ChurnTextRenderer $renderer,
        readonly private ChurnReportHandler $report,
        readonly private ChurnValidationSpecificationFactory $validationSpecificationFactory
    ) {
        parent::__construct();
        $this->validationSpecification = $this->validationSpecificationFactory->create();
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
        $context = new ChurnCommandContext($input);

        // Validate all specifications (except custom exporters which need config)
        if (!$this->validationSpecification->isSatisfiedBy($context)) {
            $errorMessage = $this->validationSpecification->getDetailedErrorMessage($context);
            $output->writeln('<error>' . $errorMessage . '</error>');
            return self::FAILURE;
        }

        // Load configuration if provided
        if ($context->hasConfigFile()) {
            $configFile = $context->getConfigFile();
            if ($configFile !== null && !$this->loadConfiguration($configFile, $output)) {
                return self::FAILURE;
            }
        }

        // Validate custom exporters after config is loaded
        if ($context->hasReportOptions()) {
            $customExporterValidation = new CustomExporterValidationSpecification(
                $this->report->getReportFactory(),
                $this->report->getConfigService()
            );
            if (!$customExporterValidation->isSatisfiedBy($context)) {
                $errorMessage = $customExporterValidation->getErrorMessageWithContext($context);
                $output->writeln('<error>' . $errorMessage . '</error>');
                return self::FAILURE;
            }
        }

        // Load coverage reader
        $coverageReader = $this->loadCoverageReader($context, $output);
        if ($coverageReader === false) {
            return self::FAILURE;
        }

        // Calculate churn metrics
        $metrics = $this->metricsFacade->calculateChurn(
            path: $context->getPath(),
            vcsType: $context->getVcsType(),
            since: $context->getSince(),
            coverageReader: $coverageReader
        );

        // Handle report generation or display
        if ($context->hasReportOptions()) {
            return $this->report->exportToFile(
                $metrics,
                $context->getReportType(),
                $context->getReportFile()
            );
        }

        $this->renderer->renderChurnTable(metrics: $metrics);
        return self::SUCCESS;
    }

    /**
     * Load coverage reader from file
     *
     * @param ChurnCommandContext $context Command context containing coverage file information
     * @param OutputInterface $output Output interface for error messages
     * @return CoverageReportReaderInterface|null|false Returns reader instance, null if no file provided, or false on error
     */
    private function loadCoverageReader(
        ChurnCommandContext $context,
        OutputInterface $output
    ): CoverageReportReaderInterface|null|false {
        $coverageFile = $context->getCoverageFile();
        $format = $context->getCoverageFormat();

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
