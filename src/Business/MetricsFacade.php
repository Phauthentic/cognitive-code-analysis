<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChangeCounter\ChangeCounterFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnCalculator;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollector;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\CsvExporter;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\HtmlExporter;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\JsonExporter;
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
        private readonly ChangeCounterFactory $changeCounterFactory
    ) {
        $this->loadConfig(__DIR__ . '/../../config.yml');
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
     * @return array<string, array<string, mixed>>
     */
    public function calculateChurn(string $path, string $vcsType = 'git'): array
    {
        $metricsCollection = $this->getCognitiveMetrics($path);

        $counter = $this->changeCounterFactory->create($vcsType);
        foreach ($metricsCollection as $metric) {
            $metric->setTimesChanged($counter->getNumberOfChangesForFile($metric->getFilename()));
        }

        return $this->churnCalculator->calculate($metricsCollection);
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
