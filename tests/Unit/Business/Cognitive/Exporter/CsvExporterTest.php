<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business\Cognitive\Exporter;

use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\Exporter\CsvExporter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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
            0 => "Class",
            1 => "Method",
            2 => "Line Count",
            3 => "Argument Count",
            4 => "Return Count",
            5 => "Variable Count",
            6 => "Property Call Count",
            7 => "If Nesting Level",
            8 => "Else Count",
            9 => "Combined Cognitive Complexity",
        ], $header);

        $data = fgetcsv($file);
        $this->assertSame([
            0 => "TestClass",
            1 => "testMethod",
            2 => "10",
            3 => "2",
            4 => "1",
            5 => "5",
            6 => "3",
            7 => "2",
            8 => "1",
            9 => "0",
        ], $data);

        fclose($file);
    }

    public function testExportThrowsExceptionWhenFileCannotBeOpenedBecauseOfMissingDirectory(): void
    {
        $metricsCollection = new CognitiveMetricsCollection();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Directory /invalid/path does not exist');

        $this->csvExporter->export($metricsCollection, '/invalid/path/test_metrics.csv');
    }
}
