<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\EventHandler;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\FileProcessed;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\SourceFilesFound;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class VerboseHandler
{
    private float $startTime = 0.0;

    public function __construct(
        private readonly InputInterface $input,
        private readonly OutputInterface $output
    ) {
    }

    public function __invoke(SourceFilesFound|FileProcessed $event): void
    {
        if (
            $this->input->hasOption(CognitiveMetricsCommand::OPTION_DEBUG)
                && $this->input->getOption(CognitiveMetricsCommand::OPTION_DEBUG) === false
        ) {
            return;
        }

        if ($event instanceof SourceFilesFound) {
            $this->startTime = microtime(true);
        }

        if ($event instanceof FileProcessed) {
            $runtime = (microtime(true) - $this->startTime);

            $this->output->writeln('Processed ' . $event->file->getRealPath());
            $this->output->writeln(' Memory: ' . $this->formatBytes(memory_get_usage(true)) . ' || Total Time: ' . round($runtime, 4) . 's');
        }
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
