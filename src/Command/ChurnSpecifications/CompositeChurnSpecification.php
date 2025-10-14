<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications;

/**
 * @SuppressWarnings("LongClassName")
 */
class CompositeChurnSpecification implements ChurnCommandSpecification
{
    /**
     * @param ChurnCommandSpecification[] $specifications
     */
    public function __construct(
        private readonly array $specifications
    ) {
    }

    public function isSatisfiedBy(ChurnCommandContext $context): bool
    {
        foreach ($this->specifications as $specification) {
            if (!$specification->isSatisfiedBy($context)) {
                return false;
            }
        }
        return true;
    }

    public function getErrorMessage(): string
    {
        return 'Validation failed';
    }

    public function getDetailedErrorMessage(ChurnCommandContext $context): string
    {
        foreach ($this->specifications as $specification) {
            if (!$specification->isSatisfiedBy($context)) {
                // Use context-specific error message if available
                if (method_exists($specification, 'getErrorMessageWithContext')) {
                    return $specification->getErrorMessageWithContext($context);
                }
                return $specification->getErrorMessage();
            }
        }
        return '';
    }

    public function getFirstFailedSpecification(ChurnCommandContext $context): ?ChurnCommandSpecification
    {
        foreach ($this->specifications as $specification) {
            if (!$specification->isSatisfiedBy($context)) {
                return $specification;
            }
        }
        return null;
    }
}
