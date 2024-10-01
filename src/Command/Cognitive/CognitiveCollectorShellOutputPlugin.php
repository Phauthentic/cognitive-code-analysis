<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\FindMetricsPluginInterface;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsCommand;
use SplFileInfo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class CognitiveCollectorShellOutputPlugin implements FindMetricsPluginInterface
{
    private float $startTime;
    private int $count = 1;

    public function __construct(
        private readonly InputInterface $input,
        private readonly OutputInterface $output
    ) {
    }

    public function beforeFindMetrics(SplFileInfo $fileInfo): void
    {
        $this->startTime = microtime(true);
    }

    public function afterFindMetrics(SplFileInfo $fileInfo): void
    {
        if (
            $this->input->hasOption(CognitiveMetricsCommand::OPTION_DEBUG)
                && $this->input->getOption(CognitiveMetricsCommand::OPTION_DEBUG) === false
        ) {
            return;
        }

        $runtime = microtime(true) - $this->startTime;

        $this->output->writeln('Processed ' . $fileInfo->getRealPath());
        $this->output->writeln('Number: ' . $this->count . ' Memory: ' . $this->formatBytes(memory_get_usage(true)) . ' -- Runtime: ' . round($runtime, 4) . 's');

        $this->count++;
    }

    public function beforeIteration(iterable $files): void
    {
    }

    public function afterIteration(CognitiveMetricsCollection $metricsCollection): void
    {
    }

    /**
     * Converts memory size to a human-readable format (bytes, KB, MB, GB, TB).
     *
     * @param int $size Memory size in bytes.
     * @return string Human-readable memory size.
     */
    private function formatBytes(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}
