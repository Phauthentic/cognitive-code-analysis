<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\GitHubActionsReport;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GitHubActionsReporterTest extends TestCase
{
    private string $filename;

    private CognitiveConfig $configAboveThreshold;

    private CognitiveConfig $configBelowThreshold;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filename = sys_get_temp_dir() . '/github_actions_' . uniqid() . '.txt';
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
    public function testExportWithViolationsWritesWorkflowCommands(): void
    {
        $metrics = $this->createMetric('App\Example', 'foo', 'src/Example.php', 42, 15.5);
        $collection = new CognitiveMetricsCollection();
        $collection->add($metrics);

        $report = new GitHubActionsReport($this->configAboveThreshold);
        $report->export($collection, $this->filename);

        $this->assertFileExists($this->filename);
        $content = file_get_contents($this->filename);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('::warning file=src/Example.php,line=42::', $content);
        $this->assertStringContainsString('cognitive complexity 15.5 (threshold: 10', $content);
    }

    #[Test]
    public function testExportWithNoViolationsWritesEmptyFile(): void
    {
        $metrics = $this->createMetric('App\Example', 'bar', 'src/Example.php', 10, 5.0);
        $collection = new CognitiveMetricsCollection();
        $collection->add($metrics);

        $report = new GitHubActionsReport($this->configBelowThreshold);
        $report->export($collection, $this->filename);

        $this->assertFileExists($this->filename);
        $this->assertSame('', file_get_contents($this->filename));
    }

    #[Test]
    public function testExportThrowsWhenDirectoryMissing(): void
    {
        $collection = new CognitiveMetricsCollection();
        $report = new GitHubActionsReport($this->configAboveThreshold);

        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Directory /nonexistent/path does not exist');

        $report->export($collection, '/nonexistent/path/report.txt');
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
