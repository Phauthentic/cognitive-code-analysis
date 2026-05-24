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
                return $this->resolveSpecificationErrorMessage($specification, $context);
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

    private function resolveSpecificationErrorMessage(
        ChurnCommandSpecification $specification,
        ChurnCommandContext $context
    ): string {
        return match (true) {
            $specification instanceof CustomExporter,
            $specification instanceof CoverageFileExists,
            $specification instanceof CoverageFormatSupported =>
                $specification->getErrorMessageWithContext($context),
            default => $specification->getErrorMessage(),
        };
    }
}
