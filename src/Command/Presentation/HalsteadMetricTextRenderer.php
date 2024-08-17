<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Command\Presentation;

use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetrics;
use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollection;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class HalsteadMetricTextRenderer
 *
 * Renders Halstead Metrics in a textual format using Symfony's console output.
 */
class HalsteadMetricTextRenderer
{
    public function render(HalsteadMetricsCollection $metricsCollection, OutputInterface $output): void
    {
        foreach ($metricsCollection as $metrics) {
            $output->writeln("<info>Class: " . $metrics->getclass() . " </info>");
            $output->writeln("<info>File: " . $metrics->getFile() . " </info>");

            $table = new Table($output);
            $table->setStyle('box');
            $table->setHeaders($this->getTableHeaders());

            $rows = [];
            $data = $this->prepareTableRow($metrics);
            $rows[] = array_values($data);

            $table->setRows($rows);

            $table->render();
            $output->writeln("");
        }
    }

    /**
     * @return string[]
     */
    protected function getTableHeaders(): array
    {
        return [
            "n1 Distinct\nOperators",
            "n2 Distinct\nOperands",
            "Total\nOperators",
            "Total\nOperands",
            "Program\nLength",
            "Program\nVocabulary",
            "Volume",
            "Difficulty",
            "Effort",
            "Possible\nBugs"
        ];
    }

    /**
     * @param \Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetrics $metrics
     * @return array<string, mixed>
     */
    protected function prepareTableRow(HalsteadMetrics $metrics): array
    {
        return [
            'n1' => $metrics->getN1(),
            'n2' => $metrics->getN2(),
            'N1' => $metrics->getTotalOperators(),
            'N2' => $metrics->getTotalOperands(),
            'programLength' => $metrics->getProgramLength(),
            'programVocabulary' => $metrics->getProgramVocabulary(),
            'volume' => round($metrics->getVolume(), 2),
            'difficulty' => round($metrics->getDifficulty(), 2),
            'effort' => round($metrics->getEffort(), 2),
            'possibleBugs' => round($metrics->getPossibleBugs(), 2)
        ];
    }
}
