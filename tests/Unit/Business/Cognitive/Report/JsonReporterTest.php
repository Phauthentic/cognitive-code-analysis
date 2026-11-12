<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\JsonReport;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test case for JsonReport class.
 */
class JsonReporterTest extends TestCase
{
    #[Test]
    public function testExport(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'json_exporter_test_') . '.json';

        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = new CognitiveMetrics([
            'class' => 'TestClass1',
            'method' => 'testMethod1',
            'file' => 'TestClass1.php',
            'lineCount' => 10,
            'argCount' => 2,
            'returnCount' => 1,
            'variableCount' => 5,
            'propertyCallCount' => 3,
            'ifCount' => 4,
            'ifNestingLevel' => 2,
            'elseCount' => 1,
        ]);

        $metrics2 = new CognitiveMetrics([
            'class' => 'TestClass2',
            'method' => 'testMethod2',
            'file' => 'TestClass2.php',
            'lineCount' => 15,
            'argCount' => 3,
            'returnCount' => 1,
            'variableCount' => 5,
            'propertyCallCount' => 3,
            'ifCount' => 4,
            'ifNestingLevel' => 2,
            'elseCount' => 1,
        ]);

        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);

        // Create an instance of JsonReport and export the metrics.
        $jsonExporter = new JsonReport();
        $jsonExporter->export($metricsCollection, $filename);

        // Read the contents of the file and decode the JSON.
        $jsonData = file_get_contents($filename);
        $decodedData = json_decode($jsonData, true);

        $expected = [
            'TestClass1' => [
                'methods' => [
                    'testMethod1' => [
                        'class' => 'TestClass1',
                        'method' => 'testMethod1',
                        'lineCount' => 10,
                        'lineCountWeight' => 0,
                        'argCount' => 2,
                        'argCountWeight' => 0,
                        'returnCount' => 1,
                        'returnCountWeight' => 0,
                        'variableCount' => 5,
                        'variableCountWeight' => 0,
                        'propertyCallCount' => 3,
                        'propertyCallCountWeight' => 0,
                        'ifCount' => 4,
                        'ifCountWeight' => 0,
                        'ifNestingLevel' => 2,
                        'ifNestingLevelWeight' => 0,
                        'elseCount' => 1,
                        'elseCountWeight' => 0,
                        'score' => 0,
                    ]
                ]
            ],
            'TestClass2' => [
                'methods' => [
                    'testMethod2' => [
                        'class' => 'TestClass2',
                        'method' => 'testMethod2',
                        'lineCount' => 15,
                        'lineCountWeight' => 0,
                        'argCount' => 3,
                        'argCountWeight' => 0,
                        'returnCount' => 1,
                        'returnCountWeight' => 0,
                        'variableCount' => 5,
                        'variableCountWeight' => 0,
                        'propertyCallCount' => 3,
                        'propertyCallCountWeight' => 0,
                        'ifCount' => 4,
                        'ifCountWeight' => 0,
                        'ifNestingLevel' => 2,
                        'ifNestingLevelWeight' => 0,
                        'elseCount' => 1,
                        'elseCountWeight' => 0,
                        'score' => 0,
                    ]
                ]
            ]
        ];

        $this->assertSame($expected, $decodedData);

        // The report is written class by class, so check it matches json_encode() of the whole array byte by byte.
        $this->assertSame(
            json_encode($decodedData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            file_get_contents($filename)
        );

        // Clean up the temporary file.
        unlink($filename);
    }

    #[Test]
    public function testExportRemovesThePartialFileWhenEncodingFails(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'json_exporter_test_') . '.json';

        $metricsCollection = new CognitiveMetricsCollection();
        $metricsCollection->add(new CognitiveMetrics([
            'class' => 'ValidClass',
            'method' => 'valid',
            'file' => 'ValidClass.php',
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0,
        ]));
        $metricsCollection->add(new CognitiveMetrics([
            'class' => "Invalid\xB1Class",
            'method' => 'invalid',
            'file' => 'InvalidClass.php',
            'lineCount' => 1,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0,
        ]));

        try {
            (new JsonReport())->export($metricsCollection, $filename);
            $this->fail('Expected a JsonException');
        } catch (\JsonException) {
        }

        $this->assertFileDoesNotExist($filename);
    }

    #[Test]
    public function testExportOfEmptyCollectionWritesAnEmptyJsonArray(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'json_exporter_test_') . '.json';

        (new JsonReport())->export(new CognitiveMetricsCollection(), $filename);

        $this->assertSame('[]', file_get_contents($filename));

        unlink($filename);
    }
}
