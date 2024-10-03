<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\EventHandler;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\FileProcessed;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\SourceFilesFound;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 *
 */
class ProgressBarHandler
{
    public function __construct(
        private readonly OutputInterface $output
    ) {
    }

    private ProgressBar $progressBar;
    private int $totalFiles = 0;
    private int $processedFiles = 0;

    public function __invoke(SourceFilesFound|FileProcessed $event): void
    {
        if ($event instanceof SourceFilesFound) {
            foreach ($event->files as $file) {
                $this->totalFiles++;
            }

            $this->output->writeln('Found ' . $this->totalFiles . ' files. Starting analysis.');
            $this->progressBar = new ProgressBar($this->output, $this->totalFiles);
        }

        if ($event instanceof FileProcessed) {
            $this->progressBar->advance(1);
            $this->processedFiles++;
        }

        if ($this->processedFiles === $this->totalFiles) {
            $this->progressBar->finish();
            $this->output->writeln('');
            $this->totalFiles = 0;
        }
    }
}
