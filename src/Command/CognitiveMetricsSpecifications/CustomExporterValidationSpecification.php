<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\CognitiveReportFactoryInterface;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;

/**
 * Validation specification for custom exporters in cognitive metrics command.
 * Ensures custom exporters are loadable before starting analysis.
 */
class CustomExporterValidationSpecification implements CognitiveMetricsSpecification
{
    public function __construct(
        private readonly CognitiveReportFactoryInterface $reportFactory,
        private readonly ConfigService $configService
    ) {
    }

    public function isSatisfiedBy(CognitiveMetricsCommandContext $context): bool
    {
        // Only validate if report options are provided
        if (!$context->hasReportOptions()) {
            return true;
        }

        $reportType = $context->getReportType();
        if ($reportType === null) {
            return true;
        }

        // Check if it's a built-in type (always valid)
        $builtInTypes = ['json', 'csv', 'html', 'markdown'];
        if (in_array($reportType, $builtInTypes, true)) {
            return true;
        }

        // For custom exporters, validate they can be loaded
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

        $config = $this->configService->getConfig();
        $customReporters = $config->customReporters['cognitive'] ?? [];

        if (!isset($customReporters[$reportType])) {
            $supportedTypes = implode('`, `', $this->reportFactory->getSupportedTypes());
            return "Custom exporter `{$reportType}` not found in configuration. Supported types: `{$supportedTypes}`";
        }

        $exporterConfig = $customReporters[$reportType];
        $class = $exporterConfig['class'] ?? '';
        $file = $exporterConfig['file'] ?? null;

        if ($file !== null && !file_exists($file)) {
            return "Exporter file not found: {$file}";
        }

        if ($file === null && !class_exists($class)) {
            return "Exporter class not found: {$class}";
        }

        return "Custom exporter `{$reportType}` validation failed";
    }

    private function validateCustomExporter(string $reportType): bool
    {
        try {
            $config = $this->configService->getConfig();
            $customReporters = $config->customReporters['cognitive'] ?? [];

            if (!isset($customReporters[$reportType])) {
                return false;
            }

            $exporterConfig = $customReporters[$reportType];
            $class = $exporterConfig['class'] ?? '';
            $file = $exporterConfig['file'] ?? null;

            // Validate file exists if specified
            if ($file !== null && !file_exists($file)) {
                return false;
            }

            // For file-based exporters, we'll do basic validation
            // The actual class loading will happen later with proper autoloading
            if ($file !== null) {
                // Check if the file is readable
                try {
                    $content = file_get_contents($file);
                    if ($content === false) {
                        return false;
                    }

                    // Basic check: does the file contain a class with the expected name?
                    // We'll look for the class name without the namespace prefix
                    $className = basename(str_replace('\\', '/', $class));
                    return strpos($content, $className) !== false;
                } catch (\Throwable) {
                    return false;
                }
            }

            // If no file specified, class should be autoloadable
            return class_exists($class);
        } catch (\Exception) {
            return false;
        }
    }
}
