<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\SemanticAnalysisSpecifications;

/**
 * Composite validation specification for semantic analysis command.
 */
class CompositeSemanticAnalysisValidationSpecification implements SemanticAnalysisValidationSpecificationInterface
{
    /**
     * @var SemanticAnalysisValidationSpecificationInterface[]
     */
    private array $specifications = [];

    public function add(SemanticAnalysisValidationSpecificationInterface $specification): void
    {
        $this->specifications[] = $specification;
    }

    public function isSatisfiedBy(SemanticAnalysisCommandContext $context): bool
    {
        foreach ($this->specifications as $specification) {
            if (!$specification->isSatisfiedBy($context)) {
                return false;
            }
        }

        return true;
    }

    public function getErrorMessage(SemanticAnalysisCommandContext $context): string
    {
        foreach ($this->specifications as $specification) {
            if (!$specification->isSatisfiedBy($context)) {
                return $specification->getErrorMessage($context);
            }
        }

        return 'Validation failed.';
    }

    public function getDetailedErrorMessage(SemanticAnalysisCommandContext $context): string
    {
        $errors = [];
        
        foreach ($this->specifications as $specification) {
            if (!$specification->isSatisfiedBy($context)) {
                $errors[] = $specification->getErrorMessage($context);
            }
        }

        if (empty($errors)) {
            return 'Validation failed.';
        }

        return implode("\n", $errors);
    }
}
