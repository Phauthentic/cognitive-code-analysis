<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Reporter;

final class CustomExporterConfigValidator
{
    /**
     * @param array<string, mixed> $exporterConfig
     * @return array{class: string, file: string|null}|null
     */
    public function parseClassAndFile(array $exporterConfig): ?array
    {
        $class = $exporterConfig['class'] ?? null;
        if (!is_string($class)) {
            return null;
        }

        $file = $exporterConfig['file'] ?? null;
        if ($file !== null && !is_string($file)) {
            return null;
        }

        return ['class' => $class, 'file' => $file];
    }

    /**
     * @param array{class: string, file: string|null} $parsed
     */
    public function isLoadable(array $parsed): bool
    {
        if ($parsed['file'] !== null) {
            return $this->isLoadableFromFile($parsed['class'], $parsed['file']);
        }

        return class_exists($parsed['class']);
    }

    /**
     * @param array<string, mixed> $exporterConfig
     */
    public function getConfigurationError(string $reportType, array $exporterConfig): string
    {
        $parsed = $this->parseClassAndFile($exporterConfig);
        if ($parsed === null) {
            return $this->resolveParseFailureMessage($reportType, $exporterConfig);
        }

        if ($parsed['file'] !== null && !file_exists($parsed['file'])) {
            return "Exporter file not found: {$parsed['file']}";
        }

        if ($parsed['file'] === null && !class_exists($parsed['class'])) {
            return "Exporter class not found: {$parsed['class']}";
        }

        return "Custom exporter `{$reportType}` validation failed";
    }

    /**
     * @param array<string, mixed> $exporterConfig
     */
    private function resolveParseFailureMessage(string $reportType, array $exporterConfig): string
    {
        if (!is_string($exporterConfig['class'] ?? null)) {
            return "Custom exporter `{$reportType}` must define a string 'class'.";
        }

        if (($exporterConfig['file'] ?? null) !== null && !is_string($exporterConfig['file'])) {
            return "Custom exporter `{$reportType}` must define a string or null 'file'.";
        }

        return "Custom exporter `{$reportType}` has invalid configuration.";
    }

    private function isLoadableFromFile(string $class, string $file): bool
    {
        if (!file_exists($file)) {
            return false;
        }

        try {
            $content = file_get_contents($file);
            if ($content === false) {
                return false;
            }

            $className = basename(str_replace('\\', '/', $class));

            return str_contains($content, $className);
        } catch (\Throwable) {
            return false;
        }
    }
}
