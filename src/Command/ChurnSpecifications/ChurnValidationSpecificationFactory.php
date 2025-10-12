<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications;

/**
 * Factory for creating the composite churn validation specification.
 */
class ChurnValidationSpecificationFactory
{
    public function create(): CompositeChurnValidationSpecification
    {
        return new CompositeChurnValidationSpecification([
            new CoverageFormatExclusivitySpecification(),
            new CoverageFileExistsSpecification(),
            new CoverageFormatSupportedSpecification(),
            new ReportOptionsCompleteSpecification(),
        ]);
    }
}
