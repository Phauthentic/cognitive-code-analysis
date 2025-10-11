<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report;

use InvalidArgumentException;
use Phauthentic\CognitiveCodeAnalysis\Business\Reporter\ReporterRegistry;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;

/**
 * Factory for creating cognitive metrics exporters.
 */
class CognitiveReportFactory implements CognitiveReportFactoryInterface
{
    private ReporterRegistry $registry;

    public function __construct(
        private readonly ConfigService $configService
    ) {
        $this->registry = new ReporterRegistry();
    }

    /**
     * Create an exporter instance based on the report type.
     *
     * @param string $type The type of exporter to create (json, csv, html, markdown)
     * @return ReportGeneratorInterface
     * @throws InvalidArgumentException If the type is not supported
     */
    public function create(string $type): ReportGeneratorInterface
    {
        $config = $this->configService->getConfig();
        $customExporters = $config->customExporters['cognitive'] ?? [];

        // Check built-in exporters first
        $builtIn = match ($type) {
            'json' => new JsonReport(),
            'csv' => new CsvReport(),
            'html' => new HtmlReport(),
            'markdown' => new MarkdownReport($config),
            default => null,
        };

        if ($builtIn !== null) {
            return $builtIn;
        }

        if (isset($customExporters[$type])) {
            return $this->createCustomExporter($customExporters[$type]);
        }

        throw new InvalidArgumentException("Unsupported exporter type: {$type}");
    }

    /**
     * Create a custom exporter instance.
     *
     * @param array<string, mixed> $config
     * @return ReportGeneratorInterface
     */
    private function createCustomExporter(array $config): ReportGeneratorInterface
    {
        $cognitiveConfig = $this->configService->getConfig();

        $this->registry->loadExporter($config['class'], $config['file'] ?? null);
        $exporter = $this->registry->instantiate(
            $config['class'],
            $config['requiresConfig'] ?? false,
            $cognitiveConfig
        );
        $this->registry->validateInterface($exporter, ReportGeneratorInterface::class);

        // PHPStan needs explicit type assertion since instantiate returns object
        assert($exporter instanceof ReportGeneratorInterface);
        return $exporter;
    }

    /**
     * Get list of supported exporter types.
     *
     * @return array<string>
     */
    public function getSupportedTypes(): array
    {
        $config = $this->configService->getConfig();
        $customExporters = $config->customExporters['cognitive'] ?? [];

        return array_merge(
            ['json', 'csv', 'html', 'markdown'],
            array_keys($customExporters)
        );
    }

    /**
     * Check if a type is supported.
     *
     * @param string $type
     * @return bool
     */
    public function isSupported(string $type): bool
    {
        return in_array($type, $this->getSupportedTypes(), true);
    }
}
