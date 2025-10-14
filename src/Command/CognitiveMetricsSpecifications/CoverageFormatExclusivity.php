<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

class CoverageFormatExclusivity implements CognitiveMetricsSpecification
{
    public function isSatisfiedBy(CognitiveMetricsCommandContext $context): bool
    {
        return !($context->hasCoberturaFile() && $context->hasCloverFile());
    }

    public function getErrorMessage(): string
    {
        return 'Only one coverage format can be specified at a time.';
    }
}
