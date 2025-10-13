<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications;

/**
 * Factory for creating the composite churn validation specification.
 */
class ChurnValidationSpecificationFactory
{
    public function create(): CompositeChurnSpecification
    {
        return new CompositeChurnSpecification([
            new CoverageFormatExclusivitySpecification(),
            new CoverageFileExistsSpecification(),
            new CoverageFormatSupportedSpecification(),
            new ReportOptionsCompleteSpecification(),
        ]);
    }
}
