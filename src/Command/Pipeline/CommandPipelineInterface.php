<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline;

use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Interface for command execution pipelines.
 * Defines the contract for executing a series of pipeline stages.
 */
interface CommandPipelineInterface
{
    /**
     * Execute the pipeline with the given execution context.
     *
     * @param ExecutionContext $context The execution context containing command state
     * @return OperationResult The result of the pipeline execution
     */
    public function execute(ExecutionContext $context): OperationResult;
}
