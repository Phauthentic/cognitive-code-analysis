<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\SemanticAnalysisSpecifications;

/**
 * Validation specification for threshold option.
 */
class ThresholdValidationSpecification implements SemanticAnalysisValidationSpecificationInterface
{
    public function isSatisfiedBy(SemanticAnalysisCommandContext $context): bool
    {
        $threshold = $context->getThreshold();
        
        if ($threshold === null) {
            return true; // Optional parameter
        }

        return $threshold >= 0.0 && $threshold <= 1.0;
    }

    public function getErrorMessage(SemanticAnalysisCommandContext $context): string
    {
        $threshold = $context->getThreshold();
        
        return "Invalid threshold: {$threshold}. Must be between 0.0 and 1.0.";
    }
}
