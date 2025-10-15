<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\SemanticAnalysisSpecifications;

/**
 * Validation specification for path argument.
 */
class PathValidationSpecification implements SemanticAnalysisValidationSpecificationInterface
{
    public function isSatisfiedBy(SemanticAnalysisCommandContext $context): bool
    {
        $path = $context->getPath();
        
        if (empty($path)) {
            return false;
        }

        return file_exists($path) || is_dir($path);
    }

    public function getErrorMessage(SemanticAnalysisCommandContext $context): string
    {
        $path = $context->getPath();
        
        if (empty($path)) {
            return 'Path argument is required.';
        }

        return "Path does not exist: {$path}";
    }
}
