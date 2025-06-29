<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\ChurnTextRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
#[AsCommand(
    name: 'churn',
    description: ''
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

    /**
     * Constructor to initialize dependencies.
     */
    public function __construct(
        private MetricsFacade $metricsFacade,
        private ChurnTextRenderer $renderer
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
                description: 'Type of report to generate (json).',
                suggestedValues: ['json', 'html', 'csv']
            )
            ->addOption(
                name: self::OPTION_REPORT_FILE,
                shortcut: 'f',
                mode: InputArgument::OPTIONAL,
                description: 'File to write the report to.'
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
        $classes = $this->metricsFacade->calculateChurn(
            path: $input->getArgument(self::ARGUMENT_PATH),
            vcsType: $input->getOption(self::OPTION_VCS),
            since: $input->getOption(self::OPTION_SINCE),
        );

        if ($this->shouldGenerateReport($input)) {
            return $this->generateReport($classes, $input, $output);
        }

        $this->renderer->renderChurnTable(
            classes: $classes
        );

        return self::SUCCESS;
    }

    /**
     * @param array<string, array<string, mixed>> $classes
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    private function generateReport(array $classes, InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->metricsFacade->exportChurnReport(
                classes: $classes,
                reportType: $input->getOption(self::OPTION_REPORT_TYPE),
                filename: $input->getOption(self::OPTION_REPORT_FILE)
            );

            return self::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln(sprintf(
                '<error>Error generating report: %s</error>',
                $exception->getMessage()
            ));

            return self::FAILURE;
        }
    }

    private function shouldGenerateReport(InputInterface $input): bool
    {
        return $input->getOption(self::OPTION_REPORT_FILE)
            && $input->getOption(self::OPTION_REPORT_TYPE);
    }
}
