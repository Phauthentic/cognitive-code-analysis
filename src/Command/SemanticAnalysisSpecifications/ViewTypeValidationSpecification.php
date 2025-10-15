<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\SemanticAnalysisSpecifications;

/**
 * Validation specification for view type option.
 */
class ViewTypeValidationSpecification implements SemanticAnalysisValidationSpecificationInterface
{
    private const VALID_VIEW_TYPES = ['top-pairs', 'matrix', 'per-entity', 'summary'];

    public function isSatisfiedBy(SemanticAnalysisCommandContext $context): bool
    {
        $viewType = $context->getViewType();
        return in_array($viewType, self::VALID_VIEW_TYPES, true);
    }

    public function getErrorMessage(SemanticAnalysisCommandContext $context): string
    {
        $viewType = $context->getViewType();
        $validViewTypes = implode(', ', self::VALID_VIEW_TYPES);
        
        return "Invalid view type: {$viewType}. Valid options: {$validViewTypes}";
    }
}
