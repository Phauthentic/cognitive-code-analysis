<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter;

use InvalidArgumentException;
use Phauthentic\CognitiveCodeAnalysis\Business\Exporter\ExporterRegistry;

/**
 * Factory for creating churn data exporters.
 */
class ChurnExporterFactory
{
    /** @var array<string, array<string, mixed>> */
    private array $customExporters = [];
    private ExporterRegistry $registry;

    /**
     * @param array<string, array<string, mixed>> $customExporters
     */
    public function __construct(array $customExporters = [])
    {
        $this->customExporters = $customExporters;
        $this->registry = new ExporterRegistry();
    }

    /**
     * Create an exporter instance based on the report type.
     *
     * @param string $type The type of exporter to create (json, csv, html, markdown, svg-treemap)
     * @return DataExporterInterface
     * @throws InvalidArgumentException If the type is not supported
     */
    public function create(string $type): DataExporterInterface
    {
        // Check built-in exporters first
        $builtIn = match ($type) {
            'json' => new JsonExporter(),
            'csv' => new CsvExporter(),
            'html' => new HtmlExporter(),
            'markdown' => new MarkdownExporter(),
            'svg-treemap', 'svg' => new SvgTreemapExporter(),
            default => null,
        };

        if ($builtIn !== null) {
            return $builtIn;
        }

        // Check custom exporters
        if (isset($this->customExporters[$type])) {
            return $this->createCustomExporter($this->customExporters[$type]);
        }

        throw new InvalidArgumentException("Unsupported exporter type: {$type}");
    }

    /**
     * Create a custom exporter instance.
     *
     * @param array<string, mixed> $config
     * @return DataExporterInterface
     */
    private function createCustomExporter(array $config): DataExporterInterface
    {
        $this->registry->loadExporter($config['class'], $config['file'] ?? null);
        $exporter = $this->registry->instantiate(
            $config['class'],
            false, // Churn exporters don't need config
            null
        );
        $this->registry->validateInterface($exporter, DataExporterInterface::class);

        // PHPStan needs explicit type assertion since instantiate returns object
        assert($exporter instanceof DataExporterInterface);
        return $exporter;
    }

    /**
     * Get list of supported exporter types.
     *
     * @return array<string>
     */
    public function getSupportedTypes(): array
    {
        return array_merge(
            ['json', 'csv', 'html', 'markdown', 'svg-treemap', 'svg'],
            array_keys($this->customExporters)
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
