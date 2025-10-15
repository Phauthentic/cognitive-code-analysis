<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\Report;

use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;

/**
 * Factory for creating semantic coupling report generators.
 */
class SemanticCouplingReportFactory implements SemanticCouplingReportFactoryInterface
{
    /**
     * @var array<string, class-string<ReportGeneratorInterface>>
     */
    private array $reportTypes = [
        'json' => JsonReport::class,
        'csv' => CsvReport::class,
        'html' => HtmlReport::class,
        'html-heatmap' => HtmlHeatmapReport::class,
        'interactive-treemap' => InteractiveTreemapReport::class,
    ];

    public function __construct(
        private readonly ConfigService $configService
    ) {
        $this->loadCustomReportTypes();
    }

    /**
     * Create a report generator for the specified type.
     *
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function create(string $reportType): ReportGeneratorInterface
    {
        if (!isset($this->reportTypes[$reportType])) {
            $availableTypes = implode(', ', array_keys($this->reportTypes));
            throw new \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException(
                "Unsupported report type: {$reportType}. Available types: {$availableTypes}"
            );
        }

        $reportClass = $this->reportTypes[$reportType];
        return new $reportClass();
    }

    /**
     * Get all available report types.
     *
     * @return array<string>
     */
    public function getAvailableReportTypes(): array
    {
        return array_keys($this->reportTypes);
    }

    /**
     * Check if a report type is supported.
     */
    public function isReportTypeSupported(string $reportType): bool
    {
        return isset($this->reportTypes[$reportType]);
    }

    /**
     * Load custom report types from configuration.
     */
    private function loadCustomReportTypes(): void
    {
        $config = $this->configService->getConfig();
        
        // Check if custom report types are configured
        if (method_exists($config, 'getCustomReportTypes')) {
            $customTypes = $config->getCustomReportTypes();
            if (is_array($customTypes)) {
                $this->reportTypes = array_merge($this->reportTypes, $customTypes);
            }
        }
    }

    /**
     * Add a custom report type.
     */
    public function addReportType(string $type, string $className): void
    {
        if (!class_exists($className)) {
            throw new \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException(
                "Report class does not exist: {$className}"
            );
        }

        if (!is_subclass_of($className, ReportGeneratorInterface::class)) {
            throw new \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException(
                "Report class must implement ReportGeneratorInterface: {$className}"
            );
        }

        $this->reportTypes[$type] = $className;
    }
}
