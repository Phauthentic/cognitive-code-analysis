<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollector;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Parser;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\DirectoryScanner;
use Phauthentic\CognitiveCodeAnalysis\Cache\Exception\CacheException;
use Phauthentic\CognitiveCodeAnalysis\Cache\FileCache;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigLoader;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 *
 */
class CognitiveMetricsCollectorTest extends TestCase
{
    private CognitiveMetricsCollector $metricsCollector;
    private ConfigService $configService;
    private MessageBusInterface $messageBus;

    /**
     * @throws CacheException
     */
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
            $bus,
            new FileCache(sys_get_temp_dir())
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
            $this->messageBus,
            new FileCache(sys_get_temp_dir())
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

    /**
     * Test that the collector handles files with anonymous classes without errors.
     * This test ensures that the full pipeline (Parser -> Collector -> CognitiveMetrics)
     * works correctly with anonymous classes and doesn't cause "Missing required keys" errors.
     */
    #[Test]
    public function testCollectWithAnonymousClasses(): void
    {
        // Create a temporary test file with anonymous classes
        $testFile = sys_get_temp_dir() . '/test_anonymous_classes.php';
        $testCode = <<<'CODE'
        <?php

        namespace TestNamespace;

        class TestClass {
            public function methodWithAnonymousClass() {
                $subscriber = new class(123) extends BaseClass {
                    public function __construct(private int $value) {
                        $this->value = $value;
                    }

                    public function process() {
                        if ($this->value > 0) {
                            return $this->value;
                        }
                        return 0;
                    }
                };

                $anotherSubscriber = new class(456) extends AnotherBaseClass {
                    public function __construct(private int $value) {
                        $this->value = $value;
                    }

                    public function handle() {
                        return $this->value * 2;
                    }
                };

                return $subscriber->process();
            }

            public function normalMethod() {
                $var = 1;
                return $var;
            }
        }
        CODE;

        file_put_contents($testFile, $testCode);

        try {
            // This should not throw any exceptions
            $metricsCollection = $this->metricsCollector->collect($testFile, $this->configService->getConfig());

            $this->assertInstanceOf(CognitiveMetricsCollection::class, $metricsCollection);
            $this->assertCount(2, $metricsCollection);

            // Verify that we can get the metrics for both methods
            $methodWithAnonymous = $metricsCollection->getClassWithMethod('\\TestNamespace\\TestClass', 'methodWithAnonymousClass');
            $normalMethod = $metricsCollection->getClassWithMethod('\\TestNamespace\\TestClass', 'normalMethod');

            $this->assertNotNull($methodWithAnonymous);
            $this->assertNotNull($normalMethod);

            // Verify that the metrics have all required properties
            $this->assertGreaterThan(0, $methodWithAnonymous->getLineCount());
            $this->assertEquals(0, $methodWithAnonymous->getArgCount());
            $this->assertGreaterThan(0, $methodWithAnonymous->getReturnCount());

            $this->assertGreaterThan(0, $normalMethod->getLineCount());
            $this->assertEquals(0, $normalMethod->getArgCount());
            $this->assertEquals(1, $normalMethod->getReturnCount());
            $this->assertEquals(1, $normalMethod->getVariableCount());
        } finally {
            // Clean up the temporary file
            if (file_exists($testFile)) {
                unlink($testFile);
            }
        }
    }

    /**
     * Test that the collector handles complex anonymous class scenarios.
     * This test ensures that the fix works in various anonymous class scenarios.
     */
    #[Test]
    public function testCollectWithComplexAnonymousClassScenarios(): void
    {
        // Create a temporary test file with complex anonymous class scenarios
        $testFile = sys_get_temp_dir() . '/test_complex_anonymous_classes.php';
        $testCode = <<<'CODE'
        <?php

        namespace TestNamespace;

        class MainClass {
            public function createAnonymousObjects() {
                // Anonymous class with constructor
                $obj1 = new class(42) {
                    public function __construct(private int $value) {
                        $this->value = $value;
                    }

                    public function getValue() {
                        return $this->value;
                    }
                };

                // Anonymous class extending another class
                $obj2 = new class extends BaseClass {
                    public function __construct() {
                        parent::__construct();
                    }

                    public function process() {
                        if (true) {
                            return 'processed';
                        }
                        return 'not processed';
                    }
                };

                // Anonymous class implementing interface
                $obj3 = new class implements SomeInterface {
                    public function __construct() {
                        $this->initialize();
                    }

                    public function initialize() {
                        $var = 1;
                        return $var;
                    }

                    public function execute() {
                        return 'executed';
                    }
                };

                return $obj1->getValue();
            }

            public function simpleMethod() {
                return 'simple';
            }
        }
        CODE;

        file_put_contents($testFile, $testCode);

        try {
            // This should not throw any exceptions
            $metricsCollection = $this->metricsCollector->collect($testFile, $this->configService->getConfig());

            $this->assertInstanceOf(CognitiveMetricsCollection::class, $metricsCollection);
            $this->assertCount(2, $metricsCollection);

            // Verify that we can get the metrics for both methods
            $createAnonymousObjects = $metricsCollection->getClassWithMethod('\\TestNamespace\\MainClass', 'createAnonymousObjects');
            $simpleMethod = $metricsCollection->getClassWithMethod('\\TestNamespace\\MainClass', 'simpleMethod');

            $this->assertNotNull($createAnonymousObjects);
            $this->assertNotNull($simpleMethod);

            // Verify that the metrics have all required properties
            $this->assertGreaterThan(0, $createAnonymousObjects->getLineCount());
            $this->assertEquals(0, $createAnonymousObjects->getArgCount());
            $this->assertGreaterThan(0, $createAnonymousObjects->getReturnCount());

            $this->assertGreaterThan(0, $simpleMethod->getLineCount());
            $this->assertEquals(0, $simpleMethod->getArgCount());
            $this->assertEquals(1, $simpleMethod->getReturnCount());
        } finally {
            // Clean up the temporary file
            if (file_exists($testFile)) {
                unlink($testFile);
            }
        }
    }

    #[Test]
    public function testCollectFromPaths(): void
    {
        $paths = [
            './tests/TestCode/Paginator.php',
            './tests/TestCode/FileWithTwoClasses.php'
        ];

        $metricsCollection = $this->metricsCollector->collectFromPaths($paths, $this->configService->getConfig());

        $this->assertInstanceOf(CognitiveMetricsCollection::class, $metricsCollection);
        $this->assertGreaterThan(2, $metricsCollection->count(), 'Should have metrics from both files');
    }

    #[Test]
    public function testCollectFromPathsWithMixedTypes(): void
    {
        $paths = [
            './tests/TestCode',  // Directory
            './tests/TestCode/Paginator.php'  // File
        ];

        $metricsCollection = $this->metricsCollector->collectFromPaths($paths, $this->configService->getConfig());

        $this->assertInstanceOf(CognitiveMetricsCollection::class, $metricsCollection);
        $this->assertGreaterThan(0, $metricsCollection->count(), 'Should have metrics from directory and file');
    }

    /**
     * @throws CognitiveAnalysisException
     * @throws CacheException
     * @throws ExceptionInterface
     */
    #[Test]
    public function testFindSourceFilesExcludePatternsNotMergedProperly(): void
    {
        $configService = new ConfigService(
            new Processor(),
            new ConfigLoader(),
        );

        $metricsCollector = new CognitiveMetricsCollector(
            new Parser(
                new ParserFactory(),
                new NodeTraverser(),
            ),
            new DirectoryScanner(),
            $configService,
            $this->messageBus,
            new FileCache(sys_get_temp_dir())
        );

        $excludePatterns = ['Paginator\.php$', 'FileWithTwoClasses\.php$'];

        $config = new CognitiveConfig(
            excludeFilePatterns: $excludePatterns,
            excludePatterns: [],
            metrics: [],
            showOnlyMethodsExceedingThreshold: false,
            scoreThreshold: 0.0
        );

        $metricsCollection = $metricsCollector->collect('./tests/TestCode', $config);

        $allFiles = [];
        foreach ($metricsCollection as $metric) {
            $allFiles[] = $metric->getFileName();
        }

        $this->assertNotContains('tests/TestCode/Paginator.php', $allFiles, 'Paginator.php should be excluded');
        $this->assertNotContains('tests/TestCode/FileWithTwoClasses.php', $allFiles, 'FileWithTwoClasses.php should be excluded');
        $this->assertContains('tests/TestCode/WpDebugData.php', $allFiles, 'WpDebugData.php should not be excluded');
    }
}
