<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business\Cognitive;

use Phauthentic\CodeQualityMetrics\Business\AbstractMetricCollector;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollector;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 *
 */
class CognitiveMetricsCollectorTest extends TestCase
{
    private AbstractMetricCollector $metricsCollector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metricsCollector = new CognitiveMetricsCollector();
    }

    public function testCollectWithValidDirectoryPath(): void
    {
        $path = './tests/TestCode';

        $metricsCollection = $this->metricsCollector->collect($path);

        $this->assertInstanceOf(CognitiveMetricsCollection::class, $metricsCollection);
        $this->assertCount(23, $metricsCollection);
    }

    public function testCollectWithValidFilePath(): void
    {
        $path = './tests/TestCode/Paginator.php';

        $metricsCollection = $this->metricsCollector->collect($path);

        $this->assertInstanceOf(CognitiveMetricsCollection::class, $metricsCollection);
        $this->assertGreaterThan(0, $metricsCollection->count(), 'CognitiveMetrics collection should not be empty');
    }

    public function testCollectWithInvalidPath(): void
    {
        $this->expectException(RuntimeException::class);
        $this->metricsCollector->collect('/invalid/path');
    }

    public function testCollectWithUnreadableFile(): void
    {
        $path = './tests/TestCode/UnreadableFile.php';

        $this->expectException(RuntimeException::class);
        $this->metricsCollector->collect($path);
    }

    /**
     * Test the collected metrics to match the expected findings.
     */
    public function testCollectedMetrics(): void
    {
        $metricsCollection = $this->metricsCollector->collect('./tests/TestCode2');
        $metrics = $metricsCollection->getClassWithMethod('\TestClassForCounts', 'test');

        $this->assertNotNull($metrics);
        $this->assertSame(5, $metrics->getArgCount());
        $this->assertSame(3, $metrics->getIfCount());
        $this->assertSame(2, $metrics->getIfNestingLevel());
        $this->assertSame(3, $metrics->getReturnCount());
        $this->assertSame(1, $metrics->getElseCount());
        $this->assertSame(3, $metrics->getVariableCount());
        $this->assertSame(2, $metrics->getPropertyCallCount());
    }
}
