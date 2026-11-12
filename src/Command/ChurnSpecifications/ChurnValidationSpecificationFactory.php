<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications;

use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;

/**
 * Factory for creating the composite churn validation specification.
 */
class ChurnValidationSpecificationFactory
{
    public function __construct(
        private readonly ConfigService $configService
    ) {
    }

    public function create(): CompositeChurnSpecification
    {
        return new CompositeChurnSpecification([
            new CoverageFormatExclusivity(),
            new CoverageFileExists(),
            new CoverageFormatSupported(),
            new ReportOptionsComplete(),
            new CustomExporter($this->configService),
        ]);
    }
}
