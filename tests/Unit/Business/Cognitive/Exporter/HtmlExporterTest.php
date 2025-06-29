<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\HtmlExporter;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class HtmlExporterTest extends TestCase
{
    private HtmlExporter $csvExporter;
    private string $filename;

    protected function setUp(): void
    {
        parent::setUp();
        $this->csvExporter = new HtmlExporter();
        $this->filename = sys_get_temp_dir() . '/test_metrics.csv';
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

    #[Test]
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

        $this->assertFileEquals(
            __DIR__ . '/HtmlExporterContent.html',
            $this->filename
        );
    }
}
