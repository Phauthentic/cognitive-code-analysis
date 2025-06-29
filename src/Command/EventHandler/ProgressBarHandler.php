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
    private ProgressBar $progressBar;
    private int $totalFiles = 0;
    private int $processedFiles = 0;

    public function __construct(
        private readonly OutputInterface $output
    ) {
    }

    public function __invoke(SourceFilesFound|FileProcessed $event): void
    {
        match (true) {
            $event instanceof SourceFilesFound => $this->handleSourceFilesFound($event),
            $event instanceof FileProcessed => $this->handleFileProcessed(),
            default => null,
        };

        if ($this->processedFiles === $this->totalFiles) {
            $this->handleAllFilesProcessed();
        }
    }

    private function handleSourceFilesFound(SourceFilesFound $event): void
    {
        $this->totalFiles = count($event->files);
        $this->output->writeln('Found ' . $this->totalFiles . ' files. Starting analysis.');
        $this->progressBar = new ProgressBar($this->output, $this->totalFiles);
    }

    private function handleFileProcessed(): void
    {
        $this->progressBar->advance(1);
        $this->processedFiles++;
    }

    private function handleAllFilesProcessed(): void
    {
        $this->progressBar->finish();
        $this->output->writeln('');
        $this->totalFiles = 0;
    }
}
