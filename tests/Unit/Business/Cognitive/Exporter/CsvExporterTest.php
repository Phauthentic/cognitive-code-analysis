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
            'line_count' => 10,
            'arg_count' => 2,
            'return_count' => 1,
            'variable_count' => 5,
            'property_call_count' => 3,
            'if_count' => 4,
            'if_nesting_level' => 2,
            'else_count' => 1,
            'line_count_weight' => 0.5,
            'arg_count_weight' => 0.3,
            'return_count_weight' => 0.2,
            'variable_count_weight' => 0.4,
            'property_call_count_weight' => 0.3,
            'if_count_weight' => 0.6,
            'if_nesting_level_weight' => 0.5,
            'else_count_weight' => 0.2,
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
