<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\MarkdownReport;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigLoader;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class MarkdownReporterTest extends TestCase
{
    private string $filename;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filename = sys_get_temp_dir() . '/test_metrics.md';
        Datetime::$fixedDate = '2023-10-01 12:00:00';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
        DateTime::$fixedDate = null;
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function configurationProvider(): array
    {
        return [
            'All metrics' => ['all-metrics-config.yml', 'MarkdownReporterContent_AllMetrics.md'],
            'Minimal' => ['minimal-config.yml', 'MarkdownReporterContent_Minimal.md'],
            'Single table' => ['single-table-config.yml', 'MarkdownReporterContent_SingleTable.md'],
            'Halstead only' => ['halstead-only-config.yml', 'MarkdownReporterContent_HalsteadOnly.md'],
            'Cyclomatic only' => ['cyclomatic-only-config.yml', 'MarkdownReporterContent_CyclomaticOnly.md'],
            'No detailed metrics' => ['no-detailed-metrics-config.yml', 'MarkdownReporterContent_NoDetailedMetrics.md'],
            'Threshold' => ['threshold-config.yml', 'MarkdownReporterContent_Threshold.md'],
        ];
    }

    #[Test]
    #[DataProvider('configurationProvider')]
    public function testExportWithConfiguration(string $configFile, string $expectedFile): void
    {
        $configService = new ConfigService(
            new Processor(),
            new ConfigLoader()
        );
        $configService->loadConfig(__DIR__ . '/../../../../Fixtures/' . $configFile);
        $config = $configService->getConfig();

        $metricsCollection = $this->createTestMetricsCollection();
        $exporter = new MarkdownReport($config);
        $exporter->export($metricsCollection, $this->filename);

        $this->assertFileEquals(
            __DIR__ . '/' . $expectedFile,
            $this->filename
        );
    }

    private function createTestMetricsCollection(): CognitiveMetricsCollection
    {
        $metricsCollection = new CognitiveMetricsCollection();

        // First metric with all data
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
            'halstead' => [
                'n1' => 10,
                'n2' => 15,
                'N1' => 50,
                'N2' => 75,
                'programLength' => 125,
                'programVocabulary' => 25,
                'volume' => 573.211,
                'difficulty' => 12.5,
                'effort' => 7165.138,
                'fqName' => 'TestClass::testMethod'
            ],
            'cyclomatic_complexity' => [
                'complexity' => 5,
                'risk_level' => 'low',
                'breakdown' => [
                    'total' => 5,
                    'base' => 1,
                    'if' => 2,
                    'elseif' => 0,
                    'else' => 1,
                    'switch' => 0,
                    'case' => 0,
                    'default' => 0,
                    'while' => 0,
                    'do_while' => 0,
                    'for' => 1,
                    'foreach' => 0,
                    'catch' => 0,
                    'logical_and' => 0,
                    'logical_or' => 0,
                    'logical_xor' => 0,
                    'ternary' => 0,
                ]
            ]
        ]);
        $metrics1->setScore(0.3);
        $metricsCollection->add($metrics1);

        // Second metric in same class
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
            'halstead' => [
                'n1' => 5,
                'n2' => 8,
                'N1' => 20,
                'N2' => 30,
                'programLength' => 50,
                'programVocabulary' => 13,
                'volume' => 185.47,
                'difficulty' => 6.25,
                'effort' => 1159.188,
                'fqName' => 'TestClass::anotherMethod'
            ],
            'cyclomatic_complexity' => [
                'complexity' => 2,
                'risk_level' => 'low',
                'breakdown' => [
                    'total' => 2,
                    'base' => 1,
                    'if' => 1,
                    'elseif' => 0,
                    'else' => 0,
                    'switch' => 0,
                    'case' => 0,
                    'default' => 0,
                    'while' => 0,
                    'do_while' => 0,
                    'for' => 0,
                    'foreach' => 0,
                    'catch' => 0,
                    'logical_and' => 0,
                    'logical_or' => 0,
                    'logical_xor' => 0,
                    'ternary' => 0,
                ]
            ]
        ]);
        $metrics2->setScore(0.05);
        $metricsCollection->add($metrics2);

        // Third metric in different class
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
            'halstead' => [
                'n1' => 20,
                'n2' => 25,
                'N1' => 100,
                'N2' => 150,
                'programLength' => 250,
                'programVocabulary' => 45,
                'volume' => 1357.824,
                'difficulty' => 25.0,
                'effort' => 33945.6,
                'fqName' => 'AnotherClass::complexMethod'
            ],
            'cyclomatic_complexity' => [
                'complexity' => 12,
                'risk_level' => 'medium',
                'breakdown' => [
                    'total' => 12,
                    'base' => 1,
                    'if' => 5,
                    'elseif' => 2,
                    'else' => 2,
                    'switch' => 1,
                    'case' => 0,
                    'default' => 0,
                    'while' => 1,
                    'do_while' => 0,
                    'for' => 0,
                    'foreach' => 0,
                    'catch' => 0,
                    'logical_and' => 0,
                    'logical_or' => 0,
                    'logical_xor' => 0,
                    'ternary' => 0,
                ]
            ]
        ]);
        $metrics3->setScore(0.8);
        $metricsCollection->add($metrics3);

        return $metricsCollection;
    }
}
