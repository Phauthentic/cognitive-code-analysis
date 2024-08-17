<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business\Halstead\Exporter;

use PHPUnit\Framework\TestCase;
use Phauthentic\CodeQualityMetrics\Business\Halstead\Exporter\CsvExporter;
use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetrics;
use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollection;
use RuntimeException;

/**
 * Unit test for the CsvExporter class
 */
class CsvExporterTest extends TestCase
{
    public function testExportToFile(): void
    {
        // Create a HalsteadMetrics object with sample data
        $metrics = new HalsteadMetrics([
            'n1' => 1,
            'n2' => 2,
            'N1' => 10,
            'N2' => 20,
            'class' => 'SomeClass',
            'file' => 'SomeFile.php'
        ]);

        // Create a HalsteadMetricsCollection and add the metrics object
        $metricsCollection = new HalsteadMetricsCollection();
        $metricsCollection->add($metrics);

        // Create a temporary file for testing
        $filename = tempnam(sys_get_temp_dir(), 'csv_export_') . '.csv';

        // Instantiate the CsvExporter and export the metrics collection to the file
        $csvExporter = new CsvExporter();
        $csvExporter->export($metricsCollection, $filename);

        // Assert that the file was created and has the expected content
        $this->assertFileExists($filename);

        $expectedContent = <<<CSV
n1,n2,N1,N2,"Program Length","Program Vocabulary",Volume,Difficulty,Effort,"Possible Bugs",Class,File
1,2,10,20,30,3,47.548875021635,5,237.74437510817,0.015849625007212,SomeClass,SomeFile.php

CSV;

        $this->assertEquals($expectedContent, file_get_contents($filename));

        // Cleanup
        unlink($filename);
    }

    public function testExportToFileWithNonExistentDirectory(): void
    {
        // Expect a RuntimeException when exporting to a non-existent directory
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Directory /nonexistent does not exist');

        // Create an empty HalsteadMetricsCollection
        $metricsCollection = new HalsteadMetricsCollection();

        // Attempt to export to a non-existent directory
        $csvExporter = new CsvExporter();
        $csvExporter->export($metricsCollection, '/nonexistent/invalid.csv');
    }

    public function testExportThrowsExceptionWhenFileIsNotWriteable(): void
    {
        // Expect a RuntimeException when file cannot be opened for writing
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Directory / is not writable');

        // Create an empty HalsteadMetricsCollection
        $metricsCollection = new HalsteadMetricsCollection();

        // Attempt to export to an unwritable file
        $csvExporter = new CsvExporter();
        $csvExporter->export($metricsCollection, '/does-not-exist');
    }
}
