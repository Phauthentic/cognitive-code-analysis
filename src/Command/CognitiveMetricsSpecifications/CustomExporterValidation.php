<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\CognitiveReportFactoryInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Reporter\CustomExporterConfigValidator;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;

/**
 * Validation specification for custom exporters in cognitive metrics command.
 * Ensures custom exporters are loadable before starting analysis.
 */
class CustomExporterValidation implements CognitiveMetricsSpecification
{
    public function __construct(
        private readonly CognitiveReportFactoryInterface $reportFactory,
        private readonly ConfigService $configService,
        private readonly CustomExporterConfigValidator $validator = new CustomExporterConfigValidator(),
    ) {
    }

    public function isSatisfiedBy(CognitiveMetricsCommandContext $context): bool
    {
        if (!$context->hasReportOptions()) {
            return true;
        }

        $reportType = $context->getReportType();
        if ($reportType === null) {
            return true;
        }

        if ($this->isBuiltInReportType($reportType)) {
            return true;
        }

        return $this->validateCustomExporter($reportType);
    }

    public function getErrorMessage(): string
    {
        return 'Custom exporter validation failed';
    }

    public function getErrorMessageWithContext(CognitiveMetricsCommandContext $context): string
    {
        $reportType = $context->getReportType();
        if ($reportType === null) {
            return 'Report type is required for validation';
        }

        $exporterConfig = $this->resolveExporterConfig($reportType);
        if ($exporterConfig === null) {
            $supportedTypes = implode('`, `', $this->reportFactory->getSupportedTypes());

            return "Custom exporter `{$reportType}` not found in configuration. Supported types: `{$supportedTypes}`";
        }

        return $this->validator->getConfigurationError($reportType, $exporterConfig);
    }

    private function validateCustomExporter(string $reportType): bool
    {
        try {
            $exporterConfig = $this->resolveExporterConfig($reportType);
            if ($exporterConfig === null) {
                return false;
            }

            $parsed = $this->validator->parseClassAndFile($exporterConfig);
            if ($parsed === null) {
                return false;
            }

            return $this->validator->isLoadable($parsed);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveExporterConfig(string $reportType): ?array
    {
        $config = $this->configService->getConfig();
        $customReporters = $config->customReporters['cognitive'] ?? [];

        if (!isset($customReporters[$reportType])) {
            return null;
        }

        $exporterConfig = $customReporters[$reportType];

        if (!is_array($exporterConfig)) {
            return null;
        }

        /** @var array<string, mixed> $exporterConfig */
        return $exporterConfig;
    }

    private function isBuiltInReportType(string $reportType): bool
    {
        return in_array($reportType, ['json', 'csv', 'html', 'markdown'], true);
    }
}
