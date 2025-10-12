<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

/**
 * @SuppressWarnings("LongClassName")
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
