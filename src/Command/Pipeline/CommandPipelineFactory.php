<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline;

use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\BaselineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\ConfigurationStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\CoverageStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\MetricsCollectionStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\OutputStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\ReportGenerationStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\SortingStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\ValidationStage;

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
