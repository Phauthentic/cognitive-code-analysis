<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

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

    public const OPTION_DEBUG = 'debug';

    /**
     * Constructor to initialize dependencies.
     */
    public function __construct(
        private MetricsFacade $metricsFacade,
        private ChurnTextRenderer $churnTextRenderer
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
                name: self::OPTION_VCS,
                shortcut: 's',
                mode: InputArgument::OPTIONAL,
                description: 'Path to a configuration file',
                default: 'git'
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
     * @SuppressWarnings("UnusedFormalParameter")
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Command status code.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $classes = $this->metricsFacade->calculateChurn(
            path: $input->getArgument(self::ARGUMENT_PATH),
            vcsType: $input->getOption(self::OPTION_VCS)
        );

        $this->churnTextRenderer->renderChurnTable(
            classes: $classes
        );

        return self::SUCCESS;
    }
}
