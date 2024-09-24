<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business;

use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollector;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\Exporter\CsvExporter;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\Exporter\HtmlExporter;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\Exporter\JsonExporter;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\ScoreCalculator;
use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollection;
use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollector;
use Phauthentic\CodeQualityMetrics\Config\ConfigService;

/**
 * Facade class for collecting and managing code quality metrics.
 */
class MetricsFacade
{
    /**
     * Constructor initializes the metrics collectors, score calculator, and config service.
     */
    public function __construct(
        private readonly HalsteadMetricsCollector $halsteadMetricsCollector,
        private readonly CognitiveMetricsCollector $cognitiveMetricsCollector,
        private readonly ScoreCalculator $scoreCalculator,
        private readonly ConfigService $configService
    ) {
        $this->loadConfig(__DIR__ . '/../../config.yml');
    }

    /**
     * Collects and returns Halstead metrics for the given path.
     *
     * @param string $path The file or directory path to collect metrics from.
     * @return HalsteadMetricsCollection The collected Halstead metrics.
     */
    public function getHalsteadMetrics(string $path): HalsteadMetricsCollection
    {
        return $this->halsteadMetricsCollector->collect($path);
    }

    /**
     * Collects and returns cognitive metrics for the given path.
     *
     * @param string $path The file or directory path to collect metrics from.
     * @return CognitiveMetricsCollection The collected cognitive metrics.
     */
    public function getCognitiveMetrics(string $path): CognitiveMetricsCollection
    {
        $metricsCollection = $this->cognitiveMetricsCollector->collect($path, $this->configService->getConfig()['cognitive']);

        foreach ($metricsCollection as $metric) {
            $this->scoreCalculator->calculate($metric, $this->configService->getConfig()['cognitive']);
        }

        return $metricsCollection;
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

    public function metricsCollectionToCsv(CognitiveMetricsCollection $metricsCollection, string $filename): void
    {
        (new CsvExporter())->export($metricsCollection, $filename);
    }

    public function metricsCollectionToJson(CognitiveMetricsCollection $metricsCollection, string $filename): void
    {
        (new JsonExporter())->export($metricsCollection, $filename);
    }

    public function metricsCollectionToHtml(CognitiveMetricsCollection $metricsCollection, string $filename): void
    {
        (new HtmlExporter())->export($metricsCollection, $filename);
    }
}
