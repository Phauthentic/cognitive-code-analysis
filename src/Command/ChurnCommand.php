<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnPipelineFactory;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications\ChurnCommandContext;
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

    public function __construct(
        readonly private ChurnPipelineFactory $pipelineFactory
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Command status code.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandContext = new ChurnCommandContext($input);
        $executionContext = new ChurnExecutionContext($commandContext, $output);

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
    private function outputExecutionSummary(ChurnExecutionContext $context, OutputInterface $output): void
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
