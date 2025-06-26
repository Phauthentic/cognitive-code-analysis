<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class ChurnTextRenderer
{
    private $churnTableHeader = [
        'Class',
        'Score',
        'Churn',
        'Times Changed',
    ];

    public function __construct(
        private readonly OutputInterface $output
    ) {
    }

    public function renderChurnTable(array $classes): void
    {
        $table = new Table($this->output);
        $table->setHeaders($this->churnTableHeader);

        foreach ($classes as $className => $data) {
            if ($data['score'] == 0) {
                continue;
            }

            $table->addRow([
                $className,
                $data['score'],
                $data['churn'] ?? 0,
                $data['timesChanged'],
            ]);
        }

        $table->render();
    }
}
