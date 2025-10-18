<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages;

use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CompositeCognitiveMetricsValidationSpecification;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\PipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline stage for validating command input specifications.
 */
class ValidationStage extends PipelineStage
{
    public function __construct(
        private readonly CompositeCognitiveMetricsValidationSpecification $specification
    ) {
    }

    public function execute(ExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();

        if (!$this->specification->isSatisfiedBy($commandContext)) {
            $errorMessage = $this->specification->getDetailedErrorMessage($commandContext);
            return OperationResult::failure($errorMessage);
        }

        return OperationResult::success();
    }

    public function shouldSkip(ExecutionContext $context): bool
    {
        return false; // Validation should never be skipped
    }

    public function getStageName(): string
    {
        return 'Validation';
    }
}
