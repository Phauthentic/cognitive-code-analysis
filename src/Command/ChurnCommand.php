<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnCalculator;
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

    public const OPTION_DEBUG = 'debug';

    /**
     * Constructor to initialize dependencies.
     */
    public function __construct(
        private MetricsFacade $metricsFacade,
        private ChurnCalculator $churnCalculator,
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
            ->addArgument(self::ARGUMENT_PATH, InputArgument::REQUIRED, 'Path to PHP files or directories to parse.')
            ->addOption(self::OPTION_CONFIG_FILE, 'c', InputArgument::OPTIONAL, 'Path to a configuration file', null)
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

        // Generate metrics for the provided path.
        $metricsCollection = $this->metricsFacade->getCognitiveMetrics($path);

        $classes = $this->churnCalculator->calculate($metricsCollection);

        $this->churnTextRenderer->renderChurnTable($classes);

        return self::SUCCESS;
    }
}
