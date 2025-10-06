<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business;

use JsonException;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChangeCounter\ChangeCounterFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnCalculator;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter\ChurnExporterFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoverageReportReaderInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollector;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\CognitiveExporterFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\ScoreCalculator;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;

/**
 * Facade class for collecting and managing code quality metrics.
 */
class MetricsFacade
{
    private ?ChurnExporterFactory $churnExporterFactory = null;
    private ?CognitiveExporterFactory $cognitiveExporterFactory = null;

    /**
     * Constructor initializes the metrics collectors, score calculator, and config service.
     */
    public function __construct(
        private readonly CognitiveMetricsCollector $cognitiveMetricsCollector,
        private readonly ScoreCalculator $scoreCalculator,
        private readonly ConfigService $configService,
        private readonly ChurnCalculator $churnCalculator,
        private readonly ChangeCounterFactory $changeCounterFactory
    ) {
        $this->loadConfig(__DIR__ . '/../../config.yml');
    }

    /**
     * Get or create the churn exporter factory.
     */
    private function getChurnExporterFactory(): ChurnExporterFactory
    {
        if ($this->churnExporterFactory === null) {
            $this->churnExporterFactory = new ChurnExporterFactory();
        }
        return $this->churnExporterFactory;
    }

    /**
     * Get or create the cognitive exporter factory.
     */
    private function getCognitiveExporterFactory(): CognitiveExporterFactory
    {
        if ($this->cognitiveExporterFactory === null) {
            $this->cognitiveExporterFactory = new CognitiveExporterFactory($this->configService->getConfig());
        }
        return $this->cognitiveExporterFactory;
    }

    /**
     * Collects and returns cognitive metrics for the given path.
     *
     * @param string $path The file or directory path to collect metrics from.
     * @return CognitiveMetricsCollection The collected cognitive metrics.
     */
    public function getCognitiveMetrics(string $path): CognitiveMetricsCollection
    {
        $metricsCollection = $this->cognitiveMetricsCollector->collect($path, $this->configService->getConfig());

        foreach ($metricsCollection as $metric) {
            $this->scoreCalculator->calculate($metric, $this->configService->getConfig());
        }

        return $metricsCollection;
    }

    /**
     * Collects and returns cognitive metrics for multiple paths.
     *
     * @param array<string> $paths Array of file or directory paths to collect metrics from.
     * @return CognitiveMetricsCollection The collected cognitive metrics from all paths.
     */
    public function getCognitiveMetricsFromPaths(array $paths): CognitiveMetricsCollection
    {
        $metricsCollection = $this->cognitiveMetricsCollector->collectFromPaths($paths, $this->configService->getConfig());

        foreach ($metricsCollection as $metric) {
            $this->scoreCalculator->calculate($metric, $this->configService->getConfig());
        }

        return $metricsCollection;
    }

    /**
     * @param string $path
     * @param string $vcsType
     * @param string $since
     * @param CoverageReportReaderInterface|null $coverageReader
     * @return array<string, array<string, mixed>>
     */
    public function calculateChurn(
        string $path,
        string $vcsType = 'git',
        string $since = '1900-01-01',
        ?CoverageReportReaderInterface $coverageReader = null
    ): array {
        $metricsCollection = $this->getCognitiveMetrics($path);

        $counter = $this->changeCounterFactory->create($vcsType);
        foreach ($metricsCollection as $metric) {
            $metric->setTimesChanged($counter->getNumberOfChangesForFile(
                filename: $metric->getFilename(),
                since: $since,
            ));
        }

        return $this->churnCalculator->calculate($metricsCollection, $coverageReader);
    }

    /**
     * Loads the configuration from the specified file path.
     *
     * @param string $configFilePath The path to the configuration file.
     * @return void
     */
    public function loadConfig(string $configFilePath): void
    {
        $this->configService->loadConfig($configFilePath);
    }

    public function getConfig(): CognitiveConfig
    {
        return $this->configService->getConfig();
    }

    /**
     * Get all ignored classes and methods from the last metrics collection.
     *
     * @return array<string, array<string, string>> Array with 'classes' and 'methods' keys
     */
    public function getIgnored(): array
    {
        return $this->cognitiveMetricsCollector->getIgnored();
    }

    /**
     * Get ignored classes from the last metrics collection.
     *
     * @return array<string, string> Array of ignored class FQCNs
     */
    public function getIgnoredClasses(): array
    {
        return $this->cognitiveMetricsCollector->getIgnoredClasses();
    }

    /**
     * Get ignored methods from the last metrics collection.
     *
     * @return array<string, string> Array of ignored method keys (ClassName::methodName)
     */
    public function getIgnoredMethods(): array
    {
        return $this->cognitiveMetricsCollector->getIgnoredMethods();
    }

    /**
     * @param array<string, array<string, mixed>> $classes
     */
    public function exportChurnReport(
        array $classes,
        string $reportType,
        string $filename
    ): void {
        $exporter = $this->getChurnExporterFactory()->create($reportType);
        $exporter->export($classes, $filename);
    }

    /**
     */
    public function exportMetricsReport(
        CognitiveMetricsCollection $metricsCollection,
        string $reportType,
        string $filename
    ): void {
        $exporter = $this->getCognitiveExporterFactory()->create($reportType);
        $exporter->export($metricsCollection, $filename);
    }

    /**
     * Clear all cached analysis results
     */
    public function clearCache(): void
    {
        // This would need to be implemented to clear the cache
        // For now, we'll add a placeholder
        throw new \RuntimeException('Cache clearing not yet implemented');
    }

    /**
     * Set cache directory override
     */
    public function setCacheDirectory(string $cacheDir): void
    {
        // This would need to be implemented to override cache directory
        // For now, we'll add a placeholder
        throw new \RuntimeException('Cache directory override not yet implemented');
    }

    /**
     * Disable caching for this run
     */
    public function disableCache(): void
    {
        // This would need to be implemented to disable caching
        // For now, we'll add a placeholder
        throw new \RuntimeException('Cache disabling not yet implemented');
    }
}
