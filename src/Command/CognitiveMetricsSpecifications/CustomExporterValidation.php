<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

use Phauthentic\CognitiveCodeAnalysis\Business\Reporter\CustomExporterConfigValidator;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;

/**
 * Validation specification for custom exporters in cognitive metrics command.
 * Ensures a configured custom exporter is loadable before starting analysis.
 * Report types without a custom exporter configuration are left to report generation.
 */
class CustomExporterValidation implements CognitiveMetricsSpecification
{
    public function __construct(
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

        $exporterConfig = $this->resolveExporterConfig($reportType);
        if ($exporterConfig === null) {
            return true;
        }

        return $this->isLoadable($exporterConfig);
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
            return $this->getErrorMessage();
        }

        return $this->validator->getConfigurationError($reportType, $exporterConfig);
    }

    /**
     * @param array<string, mixed> $exporterConfig
     */
    private function isLoadable(array $exporterConfig): bool
    {
        try {
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
}
