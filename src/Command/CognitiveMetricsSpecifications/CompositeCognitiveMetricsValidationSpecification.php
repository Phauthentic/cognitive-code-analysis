<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

/**
 * @SuppressWarnings("LongClassName")
 */
class CompositeCognitiveMetricsValidationSpecification implements CognitiveMetricsSpecification
{
    /**
     * @param CognitiveMetricsSpecification[] $specifications
     */
    public function __construct(
        private array $specifications
    ) {
    }

    public function isSatisfiedBy(CognitiveMetricsCommandContext $context): bool
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

    public function getFirstFailedSpecification(
        CognitiveMetricsCommandContext $context
    ): ?CognitiveMetricsSpecification {
        foreach ($this->specifications as $specification) {
            if (!$specification->isSatisfiedBy($context)) {
                return $specification;
            }
        }
        return null;
    }

    public function getDetailedErrorMessage(CognitiveMetricsCommandContext $context): string
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
}
