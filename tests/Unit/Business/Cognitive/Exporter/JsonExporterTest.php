<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business\Cognitive\Exporter;

use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\Exporter\JsonExporter;
use PHPUnit\Framework\TestCase;

/**
 * Test case for JsonExporter class.
 */
class JsonExporterTest extends TestCase
{
    /**
     * Tests that the JsonExporter correctly exports metrics to a JSON file.
     */
    public function testExport(): void
    {
        // Create a temporary file for testing.
        $filename = tempnam(sys_get_temp_dir(), 'json_exporter_test_') . '.json';

        // Create a CognitiveMetricsCollection and add some dummy metrics.
        $metricsCollection = new CognitiveMetricsCollection();

        $metrics1 = new CognitiveMetrics([
            'class' => 'TestClass1',
            'method' => 'testMethod1',
            'line_count' => 10,
            'arg_count' => 2,
            'return_count' => 1,
            'variable_count' => 5,
            'property_call_count' => 3,
            'if_count' => 4,
            'if_nesting_level' => 2,
            'else_count' => 1,
        ]);
        $metrics2 = new CognitiveMetrics([
            'class' => 'TestClass2',
            'method' => 'testMethod2',
            'line_count' => 15,
            'arg_count' => 3,
            'return_count' => 1,
            'variable_count' => 5,
            'property_call_count' => 3,
            'if_count' => 4,
            'if_nesting_level' => 2,
            'else_count' => 1,
        ]);

        $metricsCollection->add($metrics1);
        $metricsCollection->add($metrics2);

        // Create an instance of JsonExporter and export the metrics.
        $jsonExporter = new JsonExporter();
        $jsonExporter->export($metricsCollection, $filename);

        // Read the contents of the file and decode the JSON.
        $jsonData = file_get_contents($filename);
        $decodedData = json_decode($jsonData, true);

        $expected = [
             [
                'class' => 'TestClass1',
                'methods' => [
                    'testMethod1' => [
                        'name' => 'testMethod1',
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
                        'score' => 0
                    ]
                ]
             ],
             [
                'class' => 'TestClass2',
                'methods' => [
                    'testMethod2' => [
                        'name' => 'testMethod2',
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
                        'score' => 0
                    ]
                ]
             ]
        ];

        $this->assertSame($expected, $decodedData);

        // Clean up the temporary file.
        unlink($filename);
    }
}
