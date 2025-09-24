<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
interface CognitiveMetricTextRendererInterface
{
    /**
     * @param CognitiveMetricsCollection $metricsCollection
     * @param OutputInterface $output
     * @throws CognitiveAnalysisException
     */
    public function render(CognitiveMetricsCollection $metricsCollection, OutputInterface $output): void;
}
