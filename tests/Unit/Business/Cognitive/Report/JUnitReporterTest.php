<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\JUnitReport;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class JUnitReporterTest extends TestCase
{
    private string $filename;

    private CognitiveConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filename = sys_get_temp_dir() . '/junit_report_' . uniqid() . '.xml';
        $this->config = new CognitiveConfig(
            excludeFilePatterns: [],
            excludePatterns: [],
            metrics: [],
            showOnlyMethodsExceedingThreshold: false,
            scoreThreshold: 10.0
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (!file_exists($this->filename)) {
            return;
        }

        unlink($this->filename);
    }

    #[Test]
    public function testExportWithFailuresCreatesValidXml(): void
    {
        $over = $this->createMetric('App\Example', 'foo', 15.5);
        $under = $this->createMetric('App\Example', 'bar', 5.0);
        $collection = new CognitiveMetricsCollection();
        $collection->add($over);
        $collection->add($under);

        $report = new JUnitReport($this->config);
        $report->export($collection, $this->filename);

        $this->assertFileExists($this->filename);
        $xml = file_get_contents($this->filename);
        $this->assertNotFalse($xml);
        $this->assertStringContainsString('<testsuites', $xml);
        $this->assertStringContainsString('tests="2"', $xml);
        $this->assertStringContainsString('failures="1"', $xml);
        $this->assertStringContainsString('<failure', $xml);
        $this->assertStringContainsString('Cognitive complexity 15.5 exceeds threshold 10', $xml);

        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'Output must be valid XML');
    }

    #[Test]
    public function testExportWithNoFailuresCreatesValidXml(): void
    {
        $metric = $this->createMetric('App\Example', 'baz', 3.0);
        $collection = new CognitiveMetricsCollection();
        $collection->add($metric);

        $report = new JUnitReport($this->config);
        $report->export($collection, $this->filename);

        $this->assertFileExists($this->filename);
        $xml = file_get_contents($this->filename);
        $this->assertNotFalse($xml);
        $this->assertStringContainsString('failures="0"', $xml);
        $this->assertStringNotContainsString('<failure', $xml);
    }

    #[Test]
    public function testExportThrowsWhenDirectoryMissing(): void
    {
        $collection = new CognitiveMetricsCollection();
        $report = new JUnitReport($this->config);

        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Directory /nonexistent/path does not exist');

        $report->export($collection, '/nonexistent/path/report.xml');
    }

    private function createMetric(string $class, string $method, float $score): CognitiveMetrics
    {
        $metric = new CognitiveMetrics([
            'class' => $class,
            'method' => $method,
            'file' => 'src/Example.php',
            'line' => 42,
            'lineCount' => 10,
            'argCount' => 0,
            'returnCount' => 0,
            'variableCount' => 0,
            'propertyCallCount' => 0,
            'ifCount' => 0,
            'ifNestingLevel' => 0,
            'elseCount' => 0,
        ]);
        $metric->setScore($score);

        return $metric;
    }
}
