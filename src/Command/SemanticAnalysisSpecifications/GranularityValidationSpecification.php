<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\SemanticAnalysisSpecifications;

/**
 * Validation specification for granularity option.
 */
class GranularityValidationSpecification implements SemanticAnalysisValidationSpecificationInterface
{
    private const VALID_GRANULARITIES = ['file', 'class', 'module'];

    public function isSatisfiedBy(SemanticAnalysisCommandContext $context): bool
    {
        $granularity = $context->getGranularity();
        return in_array($granularity, self::VALID_GRANULARITIES, true);
    }

    public function getErrorMessage(SemanticAnalysisCommandContext $context): string
    {
        $granularity = $context->getGranularity();
        $validGranularities = implode(', ', self::VALID_GRANULARITIES);
        
        return "Invalid granularity: {$granularity}. Valid options: {$validGranularities}";
    }
}
