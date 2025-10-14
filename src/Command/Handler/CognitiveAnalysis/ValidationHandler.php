<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveAnalysis;

use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CustomExporterValidation;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsValidationSpecificationFactory;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveMetricsReportHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Handler for validation operations in cognitive metrics command.
 * Encapsulates all validation logic including initial validation and custom exporter validation.
 */
class ValidationHandler
{
    public function __construct(
        private readonly CognitiveMetricsValidationSpecificationFactory $specificationFactory,
        private readonly CognitiveMetricsReportHandler $reportHandler
    ) {
    }

    /**
     * Run initial validation using composite specification.
     * Returns success result if validation passes.
     * Returns failure result with detailed error message if validation fails.
     */
    public function validate(CognitiveMetricsCommandContext $context): OperationResult
    {
        $specification = $this->specificationFactory->create();

        if (!$specification->isSatisfiedBy($context)) {
            $errorMessage = $specification->getDetailedErrorMessage($context);
            return OperationResult::failure($errorMessage);
        }

        return OperationResult::success();
    }

    /**
     * Run custom exporter validation after configuration is loaded.
     * Only validates if report options are provided.
     * Returns success result if no report options or validation passes.
     * Returns failure result with detailed error message if validation fails.
     */
    public function validateCustomExporter(CognitiveMetricsCommandContext $context): OperationResult
    {
        if (!$context->hasReportOptions()) {
            return OperationResult::success();
        }

        $customExporterValidation = new CustomExporterValidation(
            $this->reportHandler->getReportFactory(),
            $this->reportHandler->getConfigService()
        );

        if (!$customExporterValidation->isSatisfiedBy($context)) {
            $errorMessage = $customExporterValidation->getErrorMessageWithContext($context);
            return OperationResult::failure($errorMessage);
        }

        return OperationResult::success();
    }
}
