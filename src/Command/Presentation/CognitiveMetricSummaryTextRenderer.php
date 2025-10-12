<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class CognitiveMetricSummaryTextRenderer implements CognitiveMetricTextRendererInterface
{
    public function __construct(
        private readonly ConfigService $configService,
    ) {
    }

    public function render(CognitiveMetricsCollection $metricsCollection, OutputInterface $output): void
    {
        $highlighted = [];
        foreach ($metricsCollection as $metric) {
            if ($metric->getScore() <= $this->configService->getConfig()->scoreThreshold) {
                continue;
            }

            $highlighted[] = $metric;
        }

        usort(
            $highlighted,
            static fn (CognitiveMetrics $alpha, CognitiveMetrics $beta) => $beta->getScore() <=> $alpha->getScore()
        );

        $output->writeln('<info>Most Complex Methods</info>');

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['Method', 'Score']);

        foreach ($highlighted as $metric) {
            $table->addRow([
                $metric->getClass() . '::' . $metric->getMethod(),
                $metric->getScore() > $this->configService->getConfig()->scoreThreshold
                    ? '<error>' . $metric->getScore() . '</error>'
                    : $metric->getScore(),
            ]);
        }

        $table->render();
        $output->writeln('');
    }
}
