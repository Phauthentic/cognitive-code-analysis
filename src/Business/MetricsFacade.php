<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business;

use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollector;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\ScoreCalculator;
use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollection;
use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollector;
use Phauthentic\CodeQualityMetrics\Config\ConfigService;

/**
 * Facade class for collecting and managing code quality metrics.
 */
class MetricsFacade
{
    private HalsteadMetricsCollector $halsteadMetricsCollector;
    private CognitiveMetricsCollector $cognitiveMetricsCollector;
    private ScoreCalculator $scoreCalculator;
    private ConfigService $configService;

    /**
     * Constructor initializes the metrics collectors, score calculator, and config service.
     */
    public function __construct()
    {
        $this->halsteadMetricsCollector = new HalsteadMetricsCollector();
        $this->cognitiveMetricsCollector = new CognitiveMetricsCollector();
        $this->scoreCalculator = new ScoreCalculator();
        $this->configService = new ConfigService();

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
}
