<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\EventHandler;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\File;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\GitChangesCountProcess;
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
        if ($event instanceof FileProcessed) {
            /*
            $command = sprintf(
                'git -C %s rev-list --since=%s --no-merges --count HEAD -- %s',
                escapeshellarg(\dirname($event->file->getRealPath())),
                escapeshellarg('2020-01-01'), // Example date, replace with actual logic if needed
                escapeshellarg($event->file->getRealPath())
            );
            echo $command . PHP_EOL;
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                //var_dump($output);
                //die();
            }

            //var_dump($event->file->getPath());
            //var_dump($output);
            echo $output[0]. PHP_EOL;
            */
        }

        if ($event instanceof SourceFilesFound) {
            $this->totalFiles = count($event->files);
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
