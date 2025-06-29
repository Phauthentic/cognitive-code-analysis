<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\CsvExporter;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class CsvExporterTest extends TestCase
{
    private CsvExporter $csvExporter;
    private string $filename;

    protected function setUp(): void
    {
        parent::setUp();
        $this->csvExporter = new CsvExporter();
        $this->filename = sys_get_temp_dir() . '/test_metrics.csv';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    public function testExportCreatesFile(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();
        $metrics = new CognitiveMetrics([
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

        $metricsCollection->add($metrics);

        $this->csvExporter->export($metricsCollection, $this->filename);

        $this->assertFileExists($this->filename);

        $file = fopen($this->filename, 'r');
        $this->assertNotFalse($file);

        $header = fgetcsv($file);

        $this->assertSame([
            0 => 'Class',
            1 => 'Method',
            2 => 'Line Count',
            3 => 'Line Count Weight',
            4 => 'Line Count Weight Delta',
            5 => 'Argument Count',
            6 => 'Argument Count Weight',
            7 => 'Argument Count Weight Delta',
            8 => 'Return Count',
            9 => 'Return Count Weight',
            10 => 'Return Count Weight Delta',
            11 => 'Variable Count',
            12 => 'Variable Count Weight',
            13 => 'Variable Count Weight Delta',
            14 => 'Property Call Count',
            15 => 'Property Call Count Weight',
            16 => 'Property Call Count Weight Delta',
            17 => 'If Nesting Level',
            18 => 'If Nesting Level Weight',
            19 => 'If Nesting Level Weight Delta',
            20 => 'Else Count',
            21 => 'Combined Cognitive Complexity'
        ], $header);

        $data = fgetcsv($file);
        $this->assertSame([
            0 => 'TestClass',
            1 => 'testMethod',
            2 => '10',
            3 => '0.5',
            4 => '',
            5 => '2',
            6 => '0.3',
            7 => '',
            8 => '1',
            9 => '0.2',
            10 => '',
            11 => '5',
            12 => '0.4',
            13 => '',
            14 => '3',
            15 => '0.3',
            16 => '',
            17 => '2',
            18 => '0.5',
            19 => '',
            20 => '1',
            21 => '0.2',
            22 => '',
            23 => '0'
        ], $data);

        fclose($file);
    }

    public function testExportThrowsExceptionWhenFileCannotBeOpenedBecauseOfMissingDirectory(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Directory /invalid/path does not exist');

        $this->csvExporter->export($metricsCollection, '/invalid/path/test_metrics.csv');
    }
}
