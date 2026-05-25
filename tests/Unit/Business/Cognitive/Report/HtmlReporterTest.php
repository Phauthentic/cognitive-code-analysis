<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\HtmlReport;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigLoader;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class HtmlReporterTest extends TestCase
{
    private string $filename;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filename = sys_get_temp_dir() . '/test_metrics.html';
        Datetime::$fixedDate = '2023-10-01 12:00:00';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
        Datetime::$fixedDate = null;
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function configurationProvider(): array
    {
        return [
            'Grouped by class' => ['all-metrics-config.yml', 'HtmlReporterContent.html', true],
            'Single table' => ['single-table-config.yml', 'HtmlReporterContent_SingleTable.html', false],
        ];
    }

    #[Test]
    #[DataProvider('configurationProvider')]
    public function testExportWithConfiguration(string $configFile, string $expectedFile, bool $useSimpleMetrics): void
    {
        $config = $this->loadConfig($configFile);
        $exporter = new HtmlReport($config);
        $metricsCollection = $useSimpleMetrics
            ? $this->createSimpleMetricsCollection()
            : $this->createMultiClassMetricsCollection();

        $exporter->export($metricsCollection, $this->filename);

        $this->assertFileEquals(__DIR__ . '/' . $expectedFile, $this->filename);
    }

    private function loadConfig(string $configFile): \Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig
    {
        $configService = new ConfigService(
            new Processor(),
            new ConfigLoader()
        );
        $configService->loadConfig(__DIR__ . '/../../../../Fixtures/' . $configFile);

        return $configService->getConfig();
    }

    private function createSimpleMetricsCollection(): CognitiveMetricsCollection
    {
        $metricsCollection = new CognitiveMetricsCollection();
        $metricsCollection->add(new CognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'testMethod',
            'file' => 'TestClass.php',
            'lineCount' => 10,
            'argCount' => 2,
            'returnCount' => 1,
            'variableCount' => 5,
            'propertyCallCount' => 3,
            'ifCount' => 4,
            'ifNestingLevel' => 2,
            'elseCount' => 1,
            'lineCountWeight' => 0.5,
            'argCountWeight' => 0.3,
            'returnCountWeight' => 0.2,
            'variableCountWeight' => 0.4,
            'propertyCallCountWeight' => 0.3,
            'ifCountWeight' => 0.6,
            'ifNestingLevelWeight' => 0.5,
            'elseCountWeight' => 0.2,
        ]));

        return $metricsCollection;
    }

    private function createMultiClassMetricsCollection(): CognitiveMetricsCollection
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = new CognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'testMethod',
            'file' => 'TestClass.php',
            'lineCount' => 10,
            'argCount' => 2,
            'returnCount' => 1,
            'variableCount' => 5,
            'propertyCallCount' => 3,
            'ifCount' => 4,
            'ifNestingLevel' => 2,
            'elseCount' => 1,
            'lineCountWeight' => 0.5,
            'argCountWeight' => 0.3,
            'returnCountWeight' => 0.2,
            'variableCountWeight' => 0.4,
            'propertyCallCountWeight' => 0.3,
            'ifCountWeight' => 0.6,
            'ifNestingLevelWeight' => 0.5,
            'elseCountWeight' => 0.2,
        ]);
        $metrics1->setScore(0.3);
        $metricsCollection->add($metrics1);

        $metrics2 = new CognitiveMetrics([
            'class' => 'TestClass',
            'method' => 'anotherMethod',
            'file' => 'TestClass.php',
            'lineCount' => 5,
            'argCount' => 1,
            'returnCount' => 1,
            'variableCount' => 2,
            'propertyCallCount' => 1,
            'ifCount' => 1,
            'ifNestingLevel' => 1,
            'elseCount' => 0,
            'lineCountWeight' => 0.2,
            'argCountWeight' => 0.1,
            'returnCountWeight' => 0.1,
            'variableCountWeight' => 0.1,
            'propertyCallCountWeight' => 0.1,
            'ifCountWeight' => 0.2,
            'ifNestingLevelWeight' => 0.1,
            'elseCountWeight' => 0.0,
        ]);
        $metrics2->setScore(0.05);
        $metricsCollection->add($metrics2);

        $metrics3 = new CognitiveMetrics([
            'class' => 'AnotherClass',
            'method' => 'complexMethod',
            'file' => 'AnotherClass.php',
            'lineCount' => 20,
            'argCount' => 4,
            'returnCount' => 3,
            'variableCount' => 10,
            'propertyCallCount' => 5,
            'ifCount' => 8,
            'ifNestingLevel' => 3,
            'elseCount' => 4,
            'lineCountWeight' => 1.0,
            'argCountWeight' => 0.6,
            'returnCountWeight' => 0.5,
            'variableCountWeight' => 0.8,
            'propertyCallCountWeight' => 0.5,
            'ifCountWeight' => 1.2,
            'ifNestingLevelWeight' => 1.0,
            'elseCountWeight' => 0.6,
        ]);
        $metrics3->setScore(0.8);
        $metricsCollection->add($metrics3);

        return $metricsCollection;
    }
}
