<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business;

use Phauthentic\CodeQualityMetrics\Application;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\ScoreCalculator;
use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollector;
use Phauthentic\CodeQualityMetrics\Config\ConfigService;
use PHP_CodeSniffer\Config;
use PHPUnit\Framework\TestCase;
use Phauthentic\CodeQualityMetrics\Business\MetricsFacade;
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

    public function testGetHalsteadMetrics(): void
    {
        $halsteadMetrics = $this->metricsFacade->getHalsteadMetrics($this->testCodePath);

        $this->assertNotEmpty($halsteadMetrics);
        $this->assertCount(4, $halsteadMetrics);
    }

    public function testGetCognitiveMetrics(): void
    {
        $cognitiveMetrics = $this->metricsFacade->getCognitiveMetrics($this->testCodePath);

        $this->assertNotEmpty($cognitiveMetrics);
        $this->assertCount(23, $cognitiveMetrics);
    }

    public function testLoadConfig(): void
    {
        // Load a valid configuration file
        $this->metricsFacade->loadConfig('./tests/Fixtures/config-with-one-metric.yml');

        // Assuming the loadConfig method in ConfigService is correctly tested,
        // here we're just ensuring that it doesn't throw exceptions
        $this->assertTrue(true); // If no exception is thrown, the test passes
    }

    public function testLoadConfigWithInvalidConfigFile(): void
    {
        $this->expectException(ParseException::class);

        $this->metricsFacade->loadConfig('./does-not-exist.yml');
    }
}
