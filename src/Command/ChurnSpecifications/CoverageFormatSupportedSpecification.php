<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications;

class CoverageFormatSupportedSpecification implements ChurnCommandValidationSpecification
{
    public function isSatisfiedBy(ChurnCommandContext $context): bool
    {
        $format = $context->getCoverageFormat();
        return $format === null || in_array($format, ['cobertura', 'clover'], true);
    }

    public function getErrorMessage(): string
    {
        return 'Unsupported coverage format';
    }

    public function getErrorMessageWithContext(ChurnCommandContext $context): string
    {
        return sprintf('Unsupported coverage format: %s', $context->getCoverageFormat());
    }
}
