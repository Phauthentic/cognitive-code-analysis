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
     * @param string $type The type of exporter to create (json, csv, html, markdown, checkstyle, junit, sarif, gitlab-codequality, github-actions)
     * @return ReportGeneratorInterface
     * @throws InvalidArgumentException If the type is not supported
     */
    public function create(string $type): ReportGeneratorInterface
    {
        $config = $this->configService->getConfig();
        $customReporters = $config->customReporters['cognitive'] ?? [];

        // Check built-in exporters first
        $builtIn = match ($type) {
            'json' => new JsonReport(),
            'csv' => new CsvReport(),
            'html' => new HtmlReport($config),
            'markdown' => new MarkdownReport($config),
            'checkstyle' => new CheckstyleReport($config),
            'junit' => new JUnitReport($config),
            'sarif' => new SarifReport($config),
            'gitlab-codequality' => new GitLabCodeQualityReport($config),
            'github-actions' => new GitHubActionsReport($config),
            default => null,
        };

        if ($builtIn !== null) {
            return $builtIn;
        }

        if (isset($customReporters[$type])) {
            $exporterConfig = $customReporters[$type];
            if (!is_array($exporterConfig)) {
                throw new InvalidArgumentException("Invalid custom exporter configuration for type: {$type}");
            }

            /** @var array<string, mixed> $exporterConfig */
            return $this->createCustomExporter($exporterConfig);
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

        $class = $config['class'] ?? null;
        if (!is_string($class)) {
            throw new InvalidArgumentException('Custom exporter must define a "class" string.');
        }

        $file = $config['file'] ?? null;
        if ($file !== null && !is_string($file)) {
            throw new InvalidArgumentException('Custom exporter "file" must be a string or null.');
        }

        $this->registry->loadExporter($class, $file);
        $exporter = $this->registry->instantiate(
            $class,
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
        $customReporters = $config->customReporters['cognitive'] ?? [];

        return array_merge(
            [
                'json',
                'csv',
                'html',
                'markdown',
                'checkstyle',
                'junit',
                'sarif',
                'gitlab-codequality',
                'github-actions',
            ],
            array_keys($customReporters)
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
