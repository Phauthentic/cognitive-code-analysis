<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

class CoverageFormatSupportedSpecification implements CognitiveMetricsSpecification
{
    public function isSatisfiedBy(CognitiveMetricsCommandContext $context): bool
    {
        $format = $context->getCoverageFormat();
        return $format === null || in_array($format, ['cobertura', 'clover'], true);
    }

    public function getErrorMessage(): string
    {
        return 'Unsupported coverage format';
    }

    public function getErrorMessageWithContext(CognitiveMetricsCommandContext $context): string
    {
        return sprintf('Unsupported coverage format: %s', $context->getCoverageFormat());
    }
}
