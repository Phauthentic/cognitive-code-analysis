<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Config;

use Phauthentic\CodeQualityMetrics\Config\ConfigFactory;
use Phauthentic\CodeQualityMetrics\Config\ConfigLoader;
use Phauthentic\CodeQualityMetrics\Config\ConfigService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 *
 */
class ConfigFactoryTest extends TestCase
{
    public function testConfigFactoryFromArray(): void
    {
        $service = new ConfigService(new Processor(), new ConfigLoader());
        $service->loadConfig(__DIR__ . '/../../../config.yml');
        $config = $service->getConfig();

        $factory = new ConfigFactory();
        $config = $factory->fromArray($config);

        $this->assertSame($config->cognitive->excludeFilePatterns, []);
        $this->assertIsArray($config->cognitive->metrics);
        $this->assertNotEmpty($config->cognitive->metrics);
    }
}
