<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business;

use Phauthentic\CognitiveCodeAnalysis\Application;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test case for MetricsFacade with custom exporters.
 */
class MetricsFacadeCustomExportersTest extends TestCase
{
    private MetricsFacade $metricsFacade;
    private string $testCodePath = './tests/TestCode';

    public function setUp(): void
    {
        parent::setUp();
        $this->metricsFacade = (new Application())->get(MetricsFacade::class);
    }

    #[Test]
    public function testMetricsFacadeWithCustomExporters(): void
    {
        // Load the custom config from fixture
        $configFile = __DIR__ . '/../../Fixtures/custom-exporters-config.yml';
        $this->metricsFacade->loadConfig($configFile);
        $config = $this->metricsFacade->getConfig();

        $this->assertInstanceOf(CognitiveConfig::class, $config);
        $this->assertArrayHasKey('cognitive', $config->customExporters);
        $this->assertArrayHasKey('churn', $config->customExporters);
        $this->assertArrayHasKey('test', $config->customExporters['cognitive']);
        $this->assertArrayHasKey('test', $config->customExporters['churn']);
    }

    #[Test]
    public function testExportMetricsReportWithCustomExporter(): void
    {
        // Load the custom config from fixture
        $configFile = __DIR__ . '/../../Fixtures/custom-cognitive-exporter-config.yml';
        $this->metricsFacade->loadConfig($configFile);

        // Get metrics
        $metricsCollection = $this->metricsFacade->getCognitiveMetrics($this->testCodePath);

        // Export using the custom exporter
        $tempOutputFile = tempnam(sys_get_temp_dir(), 'custom_export_test_') . '.json';

        $this->metricsFacade->exportMetricsReport(
            $metricsCollection,
            'custom',
            $tempOutputFile
        );

        $this->assertFileExists($tempOutputFile);
        $content = file_get_contents($tempOutputFile);
        $this->assertNotEmpty($content);
        $this->assertJson($content);

        unlink($tempOutputFile);
    }

    #[Test]
    public function testExportChurnReportWithCustomExporter(): void
    {
        // Load the custom config from fixture
        $configFile = __DIR__ . '/../../Fixtures/custom-churn-exporter-config.yml';
        $this->metricsFacade->loadConfig($configFile);

        // Calculate churn
        $churnData = $this->metricsFacade->calculateChurn($this->testCodePath, 'git', '1900-01-01');

        // Export using the custom exporter
        $tempOutputFile = tempnam(sys_get_temp_dir(), 'custom_churn_export_test_') . '.json';

        $this->metricsFacade->exportChurnReport(
            $churnData,
            'custom',
            $tempOutputFile
        );

        $this->assertFileExists($tempOutputFile);
        $content = file_get_contents($tempOutputFile);
        $this->assertNotEmpty($content);
        $this->assertJson($content);

        unlink($tempOutputFile);
    }

    #[Test]
    public function testExportWithNonExistentCustomExporter(): void
    {
        // Load the custom config from fixture
        $configFile = __DIR__ . '/../../Fixtures/invalid-custom-exporter-config.yml';
        $this->metricsFacade->loadConfig($configFile);

        // Get metrics
        $metricsCollection = $this->metricsFacade->getCognitiveMetrics($this->testCodePath);

        // Try to export using the invalid custom exporter
        $tempOutputFile = tempnam(sys_get_temp_dir(), 'invalid_export_test_') . '.json';

        $this->expectException(\Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Exporter class not found: NonExistent\Exporter');

        $this->metricsFacade->exportMetricsReport(
            $metricsCollection,
            'invalid',
            $tempOutputFile
        );
    }

    #[Test]
    public function testExportWithCustomExporterRequiringConfig(): void
    {
        // Create a temporary PHP file with a custom exporter that requires config
        $tempExporterFile = tempnam(sys_get_temp_dir(), 'config_exporter_') . '.php';
        $exporterContent = <<<'PHP'
<?php
namespace TestConfigExporter;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\ReportGeneratorInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

class ConfigExporter implements ReportGeneratorInterface {
    private CognitiveConfig $config;
    
    public function __construct(CognitiveConfig $config) {
        $this->config = $config;
    }
    
    public function export(CognitiveMetricsCollection $metrics, string $filename): void {
        $data = [
            'config' => [
                'scoreThreshold' => $this->config->scoreThreshold,
                'groupByClass' => $this->config->groupByClass
            ],
            'metrics' => 'exported'
        ];
        file_put_contents($filename, json_encode($data));
    }
}
PHP;
        file_put_contents($tempExporterFile, $exporterContent);

        try {
            // Load the custom config from fixture and update the file path
            $configFile = __DIR__ . '/../../Fixtures/config-exporter-config.yml';
            $configContent = file_get_contents($configFile);
            $configContent = str_replace('file: null', "file: '{$tempExporterFile}'", $configContent);

            $tempConfigFile = tempnam(sys_get_temp_dir(), 'config_exporter_config_') . '.yml';
            file_put_contents($tempConfigFile, $configContent);

            try {
                // Load the custom config
                $this->metricsFacade->loadConfig($tempConfigFile);

                // Get metrics
                $metricsCollection = $this->metricsFacade->getCognitiveMetrics($this->testCodePath);

                // Export using the custom exporter
                $tempOutputFile = tempnam(sys_get_temp_dir(), 'config_export_test_') . '.json';

                $this->metricsFacade->exportMetricsReport(
                    $metricsCollection,
                    'config',
                    $tempOutputFile
                );

                $this->assertFileExists($tempOutputFile);
                $content = file_get_contents($tempOutputFile);
                $this->assertNotEmpty($content);

                $data = json_decode($content, true);
                $this->assertArrayHasKey('config', $data);
                $this->assertEquals(0.8, $data['config']['scoreThreshold']);
                $this->assertFalse($data['config']['groupByClass']);

                unlink($tempOutputFile);
            } finally {
                unlink($tempConfigFile);
            }
        } finally {
            unlink($tempExporterFile);
        }
    }
}
