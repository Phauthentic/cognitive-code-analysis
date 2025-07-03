<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter\JsonExporter;
use PHPUnit\Framework\Attributes\Test;

/**
 *
 */
class SvgTreemapExporterTest extends AbstractExporterTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->exporter = new JsonExporter();
        $this->filename = sys_get_temp_dir() . '/test_metrics.json';
    }

    #[Test]
    public function testExport(): void
    {
        $classes = $this->getTestData();

        $this->exporter->export($classes, $this->filename);

        $this->assertFileEquals(
            expected: __DIR__ . '/SvgTreemapExporterTest.svg',
            actual: $this->filename
        );
    }
}
