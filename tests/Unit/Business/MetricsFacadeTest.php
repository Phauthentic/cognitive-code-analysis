<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business;

use Phauthentic\CognitiveCodeAnalysis\Application;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChangeCounter\ChangeCounterFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChangeCounter\ChangeCounterInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Unit test for the MetricsFacade class.
 */
class MetricsFacadeTest extends TestCase
{
    private string $testCodePath = './tests/TestCode';

    private MetricsFacade $metricsFacade;

    public function setUp(): void
    {
        parent::setUp();
        $this->metricsFacade = (new Application())->get(MetricsFacade::class);
    }

    #[Test]
    public function testGetCognitiveMetrics(): void
    {
        $cognitiveMetrics = $this->metricsFacade->getCognitiveMetrics($this->testCodePath);

        $this->assertNotEmpty($cognitiveMetrics);
        $this->assertCount(23, $cognitiveMetrics);
    }

    #[Test]
    public function testGetCognitiveMetricsFromPaths(): void
    {
        $paths = [
            $this->testCodePath . '/Paginator.php',
            $this->testCodePath . '/FileWithTwoClasses.php'
        ];

        $cognitiveMetrics = $this->metricsFacade->getCognitiveMetricsFromPaths($paths);

        $this->assertNotEmpty($cognitiveMetrics);
        $this->assertGreaterThan(0, $cognitiveMetrics->count());
    }

    #[Test]
    public function testLoadConfig(): void
    {
        // Load a valid configuration file
        $this->metricsFacade->loadConfig('./tests/Fixtures/config-with-one-metric.yml');

        // Assuming the loadConfig method in ConfigService is correctly tested,
        // here we're just ensuring that it doesn't throw exceptions
        $this->assertTrue(true); // If no exception is thrown, the test passes
    }

    #[Test]
    public function testLoadConfigWithInvalidConfigFile(): void
    {
        $this->expectException(ParseException::class);

        $this->metricsFacade->loadConfig('./does-not-exist.yml');
    }

    #[Test]
    public function testCalculateChurnCountsChangesOncePerFile(): void
    {
        $counter = $this->createMock(ChangeCounterInterface::class);
        $factory = $this->createMock(ChangeCounterFactory::class);
        $factory->method('create')->willReturn($counter);

        $app = new Application();
        $facade = new MetricsFacade(
            $app->get(\Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollector::class),
            $app->get(\Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\ScoreCalculator::class),
            $app->get(\Phauthentic\CognitiveCodeAnalysis\Config\ConfigService::class),
            $app->get(\Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnCalculator::class),
            $factory,
            $app->get(\Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\ChurnReportFactoryInterface::class),
            $app->get(\Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\CognitiveReportFactoryInterface::class),
        );

        $files = [];
        foreach ($facade->getCognitiveMetrics($this->testCodePath) as $metric) {
            $files[$metric->getFilename()] = true;
        }

        $counter->expects($this->exactly(count($files)))
            ->method('getNumberOfChangesForFile')
            ->willReturn(3);

        $facade->calculateChurn($this->testCodePath);
    }
}
