<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;

/**
 * @SuppressWarnings("LongClassName")
 */
class CognitiveMetricsValidationSpecificationFactory
{
    public function __construct(
        private readonly ConfigService $configService
    ) {
    }

    public function create(): CompositeCognitiveMetricsValidationSpecification
    {
        return new CompositeCognitiveMetricsValidationSpecification([
            new CoverageFormatExclusivity(),
            new CoverageFileExists(),
            new CoverageFormatSupported(),
            new SortFieldValid(),
            new SortOrderValid(),
            new CustomExporterValidation($this->configService),
        ]);
    }
}
