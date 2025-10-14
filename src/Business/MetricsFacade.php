<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChangeCounter\ChangeCounterFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnCalculator;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\ChurnReportFactoryInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoverageReportReaderInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollector;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\CognitiveReportFactoryInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\ScoreCalculator;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;

/**
 * Facade class for collecting and managing code quality metrics.
 */
class MetricsFacade
{
    /**
     * Constructor initializes the metrics collectors, score calculator, and config service.
     */
    public function __construct(
        private readonly CognitiveMetricsCollector $cognitiveMetricsCollector,
        private readonly ScoreCalculator $scoreCalculator,
        private readonly ConfigService $configService,
        private readonly ChurnCalculator $churnCalculator,
        private readonly ChangeCounterFactory $changeCounterFactory,
        private readonly ChurnReportFactoryInterface $churnReportFactory,
        private readonly CognitiveReportFactoryInterface $cognitiveReportFactory
    ) {
        // Configuration will be loaded when needed
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
     * @param CoverageReportReaderInterface|null $coverageReader Optional coverage reader for coverage data.
     * @return CognitiveMetricsCollection The collected cognitive metrics from all paths.
     */
    public function getCognitiveMetricsFromPaths(
        array $paths,
        ?CoverageReportReaderInterface $coverageReader = null
    ): CognitiveMetricsCollection {
        $metricsCollection = $this->cognitiveMetricsCollector->collectFromPaths($paths, $this->configService->getConfig());

        foreach ($metricsCollection as $metric) {
            $this->scoreCalculator->calculate($metric, $this->configService->getConfig());

            // Add coverage data if reader is provided
            if ($coverageReader === null) {
                continue;
            }

            $this->addCoverageToMetric($metric, $coverageReader);
        }

        return $metricsCollection;
    }

    public function calculateChurn(
        string $path,
        string $vcsType = 'git',
        string $since = '1900-01-01',
        ?CoverageReportReaderInterface $coverageReader = null
    ): ChurnMetricsCollection {
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

    public function getConfigService(): ConfigService
    {
        return $this->configService;
    }

    public function exportChurnReport(
        ChurnMetricsCollection $metrics,
        string $reportType,
        string $filename
    ): void {
        $exporter = $this->churnReportFactory->create($reportType);
        $exporter->export($metrics, $filename);
    }

    public function exportMetricsReport(
        CognitiveMetricsCollection $metricsCollection,
        string $reportType,
        string $filename
    ): void {
        $exporter = $this->cognitiveReportFactory->create($reportType);
        $exporter->export($metricsCollection, $filename);
    }

    /**
     * Add coverage data to a metric
     */
    private function addCoverageToMetric(
        CognitiveMetrics $metric,
        CoverageReportReaderInterface $coverageReader
    ): void {
        // Strip leading backslash from class name for coverage lookup
        $className = ltrim($metric->getClass(), '\\');

        // Try to get method-level coverage first
        $coverageDetails = $coverageReader->getCoverageDetails($className);
        if ($coverageDetails !== null) {
            $this->addMethodLevelCoverage($metric, $coverageDetails);
            return;
        }

        // Fall back to class-level coverage if details not available
        $coverage = $coverageReader->getLineCoverage($className);
        if ($coverage === null) {
            return;
        }

        $metric->setCoverage($coverage);
    }

    /**
     * Add method-level coverage from coverage details
     */
    private function addMethodLevelCoverage(
        CognitiveMetrics $metric,
        CodeCoverage\CoverageDetails $coverageDetails
    ): void {
        $methods = $coverageDetails->getMethods();
        $methodName = $metric->getMethod();

        if (isset($methods[$methodName])) {
            $methodCoverage = $methods[$methodName];
            $metric->setCoverage($methodCoverage->getLineRate());
            return;
        }

        // Fall back to class-level coverage if method not found
        $metric->setCoverage($coverageDetails->getLineRate());
    }
}
