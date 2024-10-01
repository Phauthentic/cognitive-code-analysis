<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\AbstractMetricCollector;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollector;
use Phauthentic\CognitiveCodeAnalysis\Business\DirectoryScanner;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigLoader;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Config\Definition\Processor;

/**
 *
 */
class CognitiveMetricsCollectorTest extends TestCase
{
    private CognitiveMetricsCollector $metricsCollector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metricsCollector = new CognitiveMetricsCollector(
            new ParserFactory(),
            new NodeTraverser(),
            new DirectoryScanner(),
            new ConfigService(
                new Processor(),
                new ConfigLoader(),
            )
        );
    }

    public function testCollectWithValidDirectoryPath(): void
    {
        $path = './tests/TestCode';

        $metricsCollection = $this->metricsCollector->collect($path);

        $this->assertInstanceOf(CognitiveMetricsCollection::class, $metricsCollection);
        $this->assertCount(23, $metricsCollection);
    }

    public function testCollectWithExcludedClasses(): void
    {
        $configService = new ConfigService(
            new Processor(),
            new ConfigLoader(),
        );

        // It will exclude just the constructor methods
        $configService->loadConfig(__DIR__ . '/../../../Fixtures/config-with-exclude-patterns.yml');

        $metricsCollector = new CognitiveMetricsCollector(
            new ParserFactory(),
            new NodeTraverser(),
            new DirectoryScanner(),
            $configService,
        );

        $path = './tests/TestCode';

        $metricsCollection = $metricsCollector->collect($path);

        $this->assertInstanceOf(CognitiveMetricsCollection::class, $metricsCollection);
        $this->assertCount(22, $metricsCollection);
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
