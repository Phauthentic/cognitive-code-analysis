<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline;

use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline for executing churn command stages.
 */
class ChurnPipeline
{
    /**
     * @param ChurnPipelineStage[] $stages
     */
    public function __construct(
        private readonly array $stages
    ) {
    }

    /**
     * Execute the pipeline with the given execution context.
     */
    public function execute(ChurnExecutionContext $context): OperationResult
    {
        foreach ($this->stages as $stage) {
            if ($stage->shouldSkip($context)) {
                continue;
            }

            $result = $stage->execute($context);

            if ($result->isFailure()) {
                return $result;
            }
        }

        return OperationResult::success();
    }
}
