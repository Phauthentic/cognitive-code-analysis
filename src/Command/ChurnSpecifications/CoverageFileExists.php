<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications;

class CoverageFileExists implements ChurnCommandSpecification
{
    public function isSatisfiedBy(ChurnCommandContext $context): bool
    {
        $coverageFile = $context->getCoverageFile();
        return $coverageFile === null || file_exists($coverageFile);
    }

    public function getErrorMessage(): string
    {
        return 'Coverage file not found';
    }

    public function getErrorMessageWithContext(ChurnCommandContext $context): string
    {
        return sprintf('Coverage file not found: %s', $context->getCoverageFile());
    }
}
