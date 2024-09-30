<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Config;

use Phauthentic\CodeQualityMetrics\Config\ConfigFactory;
use Phauthentic\CodeQualityMetrics\Config\ConfigLoader;
use Phauthentic\CodeQualityMetrics\Config\ConfigService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

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
        $factory->fromArray($config);
    }
}
