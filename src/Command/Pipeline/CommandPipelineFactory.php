<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline;

use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages\BaselineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages\ConfigurationStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages\CoverageStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages\MetricsCollectionStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages\OutputStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages\ReportGenerationStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages\SortingStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages\ValidationStage;

/**
 * Factory for creating command pipelines with the correct stage order.
 */
class CommandPipelineFactory
{
    public function __construct(
        private readonly ValidationStage $validationStage,
        private readonly ConfigurationStage $configurationStage,
        private readonly CoverageStage $coverageStage,
        private readonly MetricsCollectionStage $metricsCollectionStage,
        private readonly BaselineStage $baselineStage,
        private readonly SortingStage $sortingStage,
        private readonly ReportGenerationStage $reportGenerationStage,
        private readonly OutputStage $outputStage
    ) {
    }

    /**
     * Create a command pipeline with the standard stage order.
     */
    public function createPipeline(): CommandPipeline
    {
        return new CommandPipeline([
            $this->validationStage,
            $this->configurationStage,
            $this->coverageStage,
            $this->metricsCollectionStage,
            $this->baselineStage,
            $this->sortingStage,
            $this->reportGenerationStage,
            $this->outputStage,
        ]);
    }
}
