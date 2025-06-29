<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter\CsvExporter;
use PHPUnit\Framework\Attributes\Test;

/**
 *
 */
class CsvExportTest extends AbstractExporterTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->exporter = new CsvExporter();
        $this->filename = sys_get_temp_dir() . '/test_metrics.csv';
    }

    #[Test]
    public function testExport(): void
    {
        $classes = $this->getTestData();

        $this->exporter->export($classes, $this->filename);

        $content = file_get_contents(__DIR__ . '/CsvExporterContent.csv');
        $this->assertSame($content, file_get_contents($this->filename));
    }
}
