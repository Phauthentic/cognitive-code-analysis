<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business;

use PHPUnit\Framework\TestCase;
use Phauthentic\CodeQualityMetrics\Business\MetricsFacade;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Unit test for the MetricsFacade class.
 */
class MetricsFacadeTest extends TestCase
{
    private $testCodePath = './tests/TestCode';

    public function testGetHalsteadMetrics(): void
    {
        $facade = new MetricsFacade();

        $halsteadMetrics = $facade->getHalsteadMetrics($this->testCodePath);

        $this->assertNotEmpty($halsteadMetrics);
        $this->assertCount(4, $halsteadMetrics);
    }

    public function testGetCognitiveMetrics(): void
    {
        $facade = new MetricsFacade();

        $cognitiveMetrics = $facade->getCognitiveMetrics($this->testCodePath);

        $this->assertNotEmpty($cognitiveMetrics);
        $this->assertCount(23, $cognitiveMetrics);
    }

    public function testLoadConfig(): void
    {
        $facade = new MetricsFacade();

        // Load a valid configuration file
        $facade->loadConfig('./tests/Fixtures/config-with-one-metric.yml');

        // Assuming the loadConfig method in ConfigService is correctly tested,
        // here we're just ensuring that it doesn't throw exceptions
        $this->assertTrue(true); // If no exception is thrown, the test passes
    }

    public function testLoadConfigWithInvalidConfigFile(): void
    {
        $facade = new MetricsFacade();

        $this->expectException(ParseException::class);

        $facade->loadConfig('./does-not-exist.yml');
    }
}
