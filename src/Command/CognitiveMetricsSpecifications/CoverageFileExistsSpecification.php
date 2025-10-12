<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

class CoverageFileExistsSpecification implements CognitiveMetricsCommandValidationSpecification
{
    public function isSatisfiedBy(CognitiveMetricsCommandContext $context): bool
    {
        $coverageFile = $context->getCoverageFile();
        return $coverageFile === null || file_exists($coverageFile);
    }

    public function getErrorMessage(): string
    {
        return 'Coverage file not found';
    }

    public function getErrorMessageWithContext(CognitiveMetricsCommandContext $context): string
    {
        return sprintf('Coverage file not found: %s', $context->getCoverageFile());
    }
}
