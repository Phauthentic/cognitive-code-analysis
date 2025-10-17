<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CommandPipelineFactory;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsCommandContext;
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

    public function __construct(
        readonly private CommandPipelineFactory $pipelineFactory
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
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandContext = new CognitiveMetricsCommandContext($input);
        $executionContext = new ExecutionContext($commandContext, $output);

        // Build pipeline with stages
        $pipeline = $this->pipelineFactory->createPipeline();

        // Execute pipeline
        $result = $pipeline->execute($executionContext);

        // Output execution summary if debug mode
        if ($input->getOption(self::OPTION_DEBUG)) {
            $this->outputExecutionSummary($executionContext, $output);
        }

        // Display error message if pipeline failed
        if ($result->isFailure()) {
            $output->writeln('<error>' . $result->getErrorMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Output execution summary with timing information.
     */
    private function outputExecutionSummary(ExecutionContext $context, OutputInterface $output): void
    {
        $timings = $context->getTimings();
        $statistics = $context->getStatistics();

        $output->writeln('<info>Execution Summary:</info>');
        $output->writeln(sprintf('  Total execution time: %.3fs', $context->getTotalTime()));

        if (!empty($timings)) {
            $output->writeln('<info>Stage timings:</info>');
            foreach ($timings as $stage => $duration) {
                $output->writeln(sprintf('    %s: %.3fs', $stage, $duration));
            }
        }

        if (empty($statistics)) {
            return;
        }

        $output->writeln('<info>Statistics:</info>');
        foreach ($statistics as $key => $value) {
            $output->writeln(sprintf('    %s: %s', $key, $value));
        }
    }
}
