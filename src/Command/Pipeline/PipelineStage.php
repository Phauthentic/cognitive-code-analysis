<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline;

use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Abstract base class for pipeline stages.
 * Provides common functionality for timing, error handling, and skip conditions.
 */
abstract class PipelineStage
{
    /**
     * Execute the pipeline stage.
     *
     * @param ExecutionContext $context The execution context
     * @return OperationResult The result of the stage execution
     */
    abstract public function execute(ExecutionContext $context): OperationResult;

    /**
     * Check if this stage should be skipped.
     * Override in stages that can be conditionally skipped.
     *
     * @param ExecutionContext $context The execution context
     * @return bool True if the stage should be skipped
     */
    public function shouldSkip(ExecutionContext $context): bool
    {
        return false;
    }

    /**
     * Get the name of this stage for logging and timing purposes.
     *
     * @return string The stage name
     */
    abstract public function getStageName(): string;
}
