<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline;

use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Command pipeline implementation using chain of responsibility pattern.
 * Executes a series of pipeline stages in sequence.
 */
class CommandPipeline implements CommandPipelineInterface
{
    /**
     * @param PipelineStage[] $stages The pipeline stages to execute
     */
    public function __construct(
        private readonly array $stages
    ) {
    }

    /**
     * Execute the pipeline with the given execution context.
     *
     * @param ExecutionContext $context The execution context containing command state
     * @return OperationResult The result of the pipeline execution
     */
    public function execute(ExecutionContext $context): OperationResult
    {
        foreach ($this->stages as $stage) {
            if ($stage->shouldSkip($context)) {
                continue;
            }

            $startTime = microtime(true);

            $result = $stage->execute($context);

            $duration = microtime(true) - $startTime;
            $context->recordTiming($stage->getStageName(), $duration);

            if ($result->isFailure()) {
                return $result;
            }
        }

        return OperationResult::success();
    }
}
