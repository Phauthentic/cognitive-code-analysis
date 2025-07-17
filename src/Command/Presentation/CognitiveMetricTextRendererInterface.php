<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 *
 */
interface CognitiveMetricTextRendererInterface
{
    /**
     * @param CognitiveMetricsCollection $metricsCollection
     * @throws CognitiveAnalysisException
     */
    public function render(CognitiveMetricsCollection $metricsCollection): void;
}
