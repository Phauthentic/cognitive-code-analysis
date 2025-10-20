<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline;

use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Interface for churn pipeline stages.
 */
interface ChurnPipelineStage
{
    /**
     * Execute the pipeline stage.
     */
    public function execute(ChurnExecutionContext $context): OperationResult;

    /**
     * Check if this stage should be skipped.
     */
    public function shouldSkip(ChurnExecutionContext $context): bool;

    /**
     * Get the name of this stage.
     */
    public function getStageName(): string;
}
