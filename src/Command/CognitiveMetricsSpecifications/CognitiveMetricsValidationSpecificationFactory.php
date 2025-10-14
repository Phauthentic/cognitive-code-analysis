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
            new CoverageFormatExclusivity(),
            new CoverageFileExists(),
            new CoverageFormatSupported(),
            new SortFieldValid(),
            new SortOrderValid(),
        ]);
    }
}
