<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\CheckstyleReport;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CheckstyleReporterTest extends TestCase
{
    private string $filename;

    private CognitiveConfig $configAboveThreshold;

    private CognitiveConfig $configBelowThreshold;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filename = sys_get_temp_dir() . '/checkstyle_report_' . uniqid() . '.xml';
        $this->configAboveThreshold = new CognitiveConfig(
            excludeFilePatterns: [],
            excludePatterns: [],
            metrics: [],
            showOnlyMethodsExceedingThreshold: true,
            scoreThreshold: 10.0
        );
        $this->configBelowThreshold = new CognitiveConfig(
            excludeFilePatterns: [],
            excludePatterns: [],
            metrics: [],
            showOnlyMethodsExceedingThreshold: true,
            scoreThreshold: 100.0
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
    public function testExportWithViolationsCreatesValidXml(): void
    {
        $metrics = $this->createMetric('App\Example', 'foo', 'src/Example.php', 42, 15.5);
        $collection = new CognitiveMetricsCollection();
        $collection->add($metrics);

        $report = new CheckstyleReport($this->configAboveThreshold);
        $report->export($collection, $this->filename);

        $this->assertFileExists($this->filename);
        $xml = file_get_contents($this->filename);
        $this->assertNotFalse($xml);
        $this->assertStringContainsString('<checkstyle', $xml);
        $this->assertStringContainsString('name="src/Example.php"', $xml);
        $this->assertStringContainsString('line="42"', $xml);
        $this->assertStringContainsString('source="CognitiveComplexity"', $xml);
        $this->assertStringContainsString('cognitive complexity', $xml);

        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'Output must be valid XML');
    }

    #[Test]
    public function testExportWithNoViolationsCreatesEmptyFiles(): void
    {
        $metrics = $this->createMetric('App\Example', 'bar', 'src/Example.php', 10, 5.0);
        $collection = new CognitiveMetricsCollection();
        $collection->add($metrics);

        $report = new CheckstyleReport($this->configBelowThreshold);
        $report->export($collection, $this->filename);

        $this->assertFileExists($this->filename);
        $xml = file_get_contents($this->filename);
        $this->assertNotFalse($xml);
        $this->assertStringContainsString('<checkstyle', $xml);
        $this->assertStringNotContainsString('<file', $xml);
    }

    #[Test]
    public function testExportThrowsWhenDirectoryMissing(): void
    {
        $collection = new CognitiveMetricsCollection();
        $report = new CheckstyleReport($this->configAboveThreshold);

        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Directory /nonexistent/path does not exist');

        $report->export($collection, '/nonexistent/path/report.xml');
    }

    private function createMetric(
        string $class,
        string $method,
        string $file,
        int $line,
        float $score
    ): CognitiveMetrics {
        $metric = new CognitiveMetrics([
            'class' => $class,
            'method' => $method,
            'file' => $file,
            'line' => $line,
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
