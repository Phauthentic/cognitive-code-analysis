<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business;

use Phauthentic\CognitiveCodeAnalysis\Application;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
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
}
