<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnStages;

use Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications\CompositeChurnSpecification;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnPipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline stage for validating churn command input specifications.
 */
class ValidationStage implements ChurnPipelineStage
{
    public function __construct(
        private readonly CompositeChurnSpecification $specification
    ) {
    }

    public function execute(ChurnExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();

        if (!$this->specification->isSatisfiedBy($commandContext)) {
            $errorMessage = $this->specification->getDetailedErrorMessage($commandContext);
            $context->getOutput()->writeln('<error>' . $errorMessage . '</error>');
            return OperationResult::failure($errorMessage);
        }

        return OperationResult::success();
    }

    public function getStageName(): string
    {
        return 'Validation';
    }

    public function shouldSkip(ChurnExecutionContext $context): bool
    {
        return false; // Validation should never be skipped
    }
}
