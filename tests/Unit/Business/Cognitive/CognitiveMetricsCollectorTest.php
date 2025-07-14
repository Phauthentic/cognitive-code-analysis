<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollector;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Parser;
use Phauthentic\CognitiveCodeAnalysis\Business\DirectoryScanner;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigLoader;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 *
 */
class CognitiveMetricsCollectorTest extends TestCase
{
    private CognitiveMetricsCollector $metricsCollector;
    private ConfigService $configService;
    private MessageBusInterface $messageBus;

    protected function setUp(): void
    {
        parent::setUp();

        $bus = $this->getMockBuilder(MessageBusInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bus->expects($this->any())
            ->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $this->messageBus = $bus;

        $this->metricsCollector = new CognitiveMetricsCollector(
            new Parser(
                new ParserFactory(),
                new NodeTraverser(),
            ),
            new DirectoryScanner(),
            new ConfigService(
                new Processor(),
                new ConfigLoader(),
            ),
            $bus
        );

        $this->configService = new ConfigService(
            new Processor(),
            new ConfigLoader(),
        );
    }

    #[Test]
    public function testCollectWithValidDirectoryPath(): void
    {
        $path = './tests/TestCode';

        $metricsCollection = $this->metricsCollector->collect($path, $this->configService->getConfig());

        $this->assertInstanceOf(CognitiveMetricsCollection::class, $metricsCollection);
        $this->assertCount(23, $metricsCollection);
    }

    #[Test]
    public function testCollectWithExcludedClasses(): void
    {
        $configService = new ConfigService(
            new Processor(),
            new ConfigLoader(),
        );

        // It will exclude just the constructor methods
        $configService->loadConfig(__DIR__ . '/../../../Fixtures/config-with-exclude-patterns.yml');

        $metricsCollector = new CognitiveMetricsCollector(
            new Parser(
                new ParserFactory(),
                new NodeTraverser(),
            ),
            new DirectoryScanner(),
            $configService,
            $this->messageBus
        );

        $path = './tests/TestCode';

        $metricsCollection = $metricsCollector->collect($path, $this->configService->getConfig());

        $this->assertInstanceOf(CognitiveMetricsCollection::class, $metricsCollection);
        $this->assertCount(22, $metricsCollection);
    }

    #[Test]
    public function testCollectWithValidFilePath(): void
    {
        $path = './tests/TestCode/Paginator.php';

        $metricsCollection = $this->metricsCollector->collect($path, $this->configService->getConfig());

        $this->assertInstanceOf(CognitiveMetricsCollection::class, $metricsCollection);
        $this->assertGreaterThan(0, $metricsCollection->count(), 'CognitiveMetrics collection should not be empty');
    }

    #[Test]
    public function testCollectWithInvalidPath(): void
    {
        $this->expectException(CognitiveAnalysisException::class);
        $this->metricsCollector->collect('/invalid/path', $this->configService->getConfig());
    }

    #[Test]
    public function testCollectWithUnreadableFile(): void
    {
        $path = './tests/TestCode/UnreadableFile.php';

        $this->expectException(CognitiveAnalysisException::class);
        $this->metricsCollector->collect($path, $this->configService->getConfig());
    }

    /**
     * Test the collected metrics to match the expected findings.
     */
    #[Test]
    public function testCollectedMetrics(): void
    {
        $metricsCollection = $this->metricsCollector->collect('./tests/TestCode2', $this->configService->getConfig());
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
