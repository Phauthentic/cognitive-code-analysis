<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\EventHandler;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\ParserFailed;
use Symfony\Component\Console\Output\OutputInterface;

class ParserErrorHandler
{
    public function __construct(
        private readonly OutputInterface $output,
    ) {
    }

    public function __invoke(ParserFailed $event): void
    {
        $this->output->writeln(
            sprintf(
                '<error>Parser failed for file %s: %s</error>',
                $event->file->getRealPath(),
                $event->throwable->getMessage()
            )
        );
        $this->output->writeln(
            sprintf(
                '<comment>Stack trace:</comment> %s',
                $event->throwable->getTraceAsString()
            )
        );
        $this->output->writeln(
            sprintf(
                '<info>Please create an issue on Github and provide the code that could not pe parsed.</info> %s',
                $event->throwable->getTraceAsString()
            )
        );
    }
}
