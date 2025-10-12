<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\MarkdownReport;
use PHPUnit\Framework\Attributes\Test;

/**
 *
 */
class MarkdownReporterTest extends AbstractReporterTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->exporter = new MarkdownReport();
        $this->filename = sys_get_temp_dir() . '/test_metrics.md';
    }

    #[Test]
    public function testExport(): void
    {
        $classes = $this->getTestData();

        $this->exporter->export($classes, $this->filename);

        $this->assertFileEquals(
            expected: __DIR__ . '/MarkdownReporterContent.md',
            actual: $this->filename
        );
    }

    #[Test]
    public function testExportWithCoverage(): void
    {
        $classes = $this->getTestDataWithCoverage();

        $this->exporter->export($classes, $this->filename);

        $this->assertFileEquals(
            expected: __DIR__ . '/MarkdownReporterContentWithCoverage.md',
            actual: $this->filename
        );
    }
}
