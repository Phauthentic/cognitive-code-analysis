<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\SemanticAnalysisSpecifications;

/**
 * Validation specification for semantic analysis command.
 */
interface SemanticAnalysisValidationSpecificationInterface
{
    public function isSatisfiedBy(SemanticAnalysisCommandContext $context): bool;

    public function getErrorMessage(SemanticAnalysisCommandContext $context): string;
}
