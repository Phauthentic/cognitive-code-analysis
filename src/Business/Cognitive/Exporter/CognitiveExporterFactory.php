<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter;

use InvalidArgumentException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

/**
 * Factory for creating cognitive metrics exporters.
 */
class CognitiveExporterFactory
{
    public function __construct(
        private readonly CognitiveConfig $config
    ) {
    }

    /**
     * Create an exporter instance based on the report type.
     *
     * @param string $type The type of exporter to create (json, csv, html, markdown)
     * @return DataExporterInterface
     * @throws InvalidArgumentException If the type is not supported
     */
    public function create(string $type): DataExporterInterface
    {
        return match ($type) {
            'json' => new JsonExporter(),
            'csv' => new CsvExporter(),
            'html' => new HtmlExporter(),
            'markdown' => new MarkdownExporter($this->config),
            default => throw new InvalidArgumentException("Unsupported exporter type: {$type}"),
        };
    }

    /**
     * Get list of supported exporter types.
     *
     * @return array<string>
     */
    public function getSupportedTypes(): array
    {
        return ['json', 'csv', 'html', 'markdown'];
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
