<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter\MarkdownExporter;
use PHPUnit\Framework\Attributes\Test;

/**
 *
 */
class MarkdownExporterTest extends AbstractExporterTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->exporter = new MarkdownExporter();
        $this->filename = sys_get_temp_dir() . '/test_metrics.md';
    }

    #[Test]
    public function testExport(): void
    {
        $classes = $this->getTestData();

        $this->exporter->export($classes, $this->filename);

        $this->assertFileEquals(
            expected: __DIR__ . '/MarkdownExporterContent.md',
            actual: $this->filename
        );
    }

    #[Test]
    public function testExportWithCoverage(): void
    {
        $classes = $this->getTestDataWithCoverage();

        $this->exporter->export($classes, $this->filename);

        $this->assertFileEquals(
            expected: __DIR__ . '/MarkdownExporterContentWithCoverage.md',
            actual: $this->filename
        );
    }
}
