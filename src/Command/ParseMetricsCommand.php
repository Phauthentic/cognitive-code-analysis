<?php

declare(strict_types=1);

namespace Phauthentic\CodeQuality\Command;

use Phauthentic\CodeQuality\Business\MetricsFacade;
use Phauthentic\CodeQuality\Command\Presentation\MetricTextRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
#[AsCommand(
    name: 'cognitive:analyze',
)]
class ParseMetricsCommand extends Command
{
    private const OPTION_EXPORT_JSON = 'export-json';
    private const OPTION_EXPORT_CSV = 'export-csv';
    private const OPTION_CONFIG_FILE = 'config';

    private const ARGUMENT_PATH = 'path';

    protected function configure(): void
    {
        $this
            ->setDescription('Parse PHP files or directories and output method metrics.')
            ->addOption(self::OPTION_EXPORT_CSV, null, InputArgument::OPTIONAL, 'Writes a CSV file', null)
            ->addOption(self::OPTION_EXPORT_JSON, null, InputArgument::OPTIONAL, 'Writes a JSON file', null)
            ->addOption(self::OPTION_CONFIG_FILE, null, InputArgument::OPTIONAL, 'Path to a configuration file', null)
            ->addArgument(self::ARGUMENT_PATH, InputArgument::REQUIRED, 'Path to PHP files or directories to parse.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');

        $metricsFacade = new MetricsFacade();
        $metricsCollection = $metricsFacade->getMetrics($path);

        (new MetricTextRenderer())->render($metricsCollection, $output);

        if ($input->getOption(self::OPTION_CONFIG_FILE) !== null) {

        }

        if ($input->getOption(self::OPTION_EXPORT_CSV) !== null) {
            $metricsFacade->metricsCollectionToCsv($metricsCollection, './metrics.csv');
        }

        if ($input->getOption(self::OPTION_EXPORT_JSON) !== null) {
            $metricsFacade->metricsCollectionToJson($metricsCollection, './metrics.json');
        }

        return Command::SUCCESS;
    }
}
