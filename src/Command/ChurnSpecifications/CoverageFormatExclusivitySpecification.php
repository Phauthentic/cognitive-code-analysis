<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications;

class CoverageFormatExclusivitySpecification implements ChurnCommandSpecification
{
    public function isSatisfiedBy(ChurnCommandContext $context): bool
    {
        return !($context->hasCoberturaFile() && $context->hasCloverFile());
    }

    public function getErrorMessage(): string
    {
        return 'Only one coverage format can be specified at a time.';
    }
}
