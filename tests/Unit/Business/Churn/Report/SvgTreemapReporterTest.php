<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\SvgTreemapReport;
use PHPUnit\Framework\Attributes\Test;

class SvgTreemapReporterTest extends AbstractReporterTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->exporter = new SvgTreemapReport();
        $this->filename = sys_get_temp_dir() . '/test_metrics.json';
    }

    #[Test]
    public function testExport(): void
    {
        $classes = $this->getTestData();

        $this->exporter->export($classes, $this->filename);

        $this->assertFileEquals(
            expected: __DIR__ . '/SvgTreemapReporterTest.svg',
            actual: $this->filename
        );
    }
}
