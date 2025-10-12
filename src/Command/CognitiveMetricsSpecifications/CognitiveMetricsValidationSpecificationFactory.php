<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

/**
 * Factory for creating the composite validation specification.
 */
class CognitiveMetricsValidationSpecificationFactory
{
    public function create(): CompositeCognitiveMetricsValidationSpecification
    {
        return new CompositeCognitiveMetricsValidationSpecification([
            new CoverageFormatExclusivitySpecification(),
            new CoverageFileExistsSpecification(),
            new CoverageFormatSupportedSpecification(),
            new SortFieldValidSpecification(),
            new SortOrderValidSpecification(),
        ]);
    }
}
