<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline;

use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnStages\ChurnCalculationStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnStages\ConfigurationStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnStages\CoverageStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnStages\OutputStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnStages\ReportGenerationStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnStages\ValidationStage;

/**
 * Factory for creating churn command pipelines with the correct stage order.
 */
class ChurnPipelineFactory
{
    public function __construct(
        private readonly ValidationStage $validationStage,
        private readonly ConfigurationStage $configurationStage,
        private readonly CoverageStage $coverageStage,
        private readonly ChurnCalculationStage $churnCalculationStage,
        private readonly ReportGenerationStage $reportGenerationStage,
        private readonly OutputStage $outputStage
    ) {
    }

    /**
     * Create a churn command pipeline with the standard stage order.
     */
    public function createPipeline(): ChurnPipeline
    {
        return new ChurnPipeline([
            $this->validationStage,
            $this->configurationStage,
            $this->coverageStage,
            $this->churnCalculationStage,
            $this->reportGenerationStage,
            $this->outputStage,
        ]);
    }
}
