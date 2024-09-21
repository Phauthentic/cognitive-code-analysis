<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Config;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Definition\Processor;

/**
 *
 */
class ConfigService
{
    /**
     * @var array<string, mixed>
     */
    private array $config;
    private readonly Processor $processor;
    private readonly ConfigLoader $configuration;

    public function __construct()
    {
        $this->processor = new Processor();
        $this->configuration = new ConfigLoader();

        $this->config = $this->processor->processConfiguration($this->configuration, [
            Yaml::parseFile(__DIR__ . '/../../config.yml'),
        ]);
    }

    public function loadConfig(string $configFilePath): void
    {
        $this->config = $this->processor->processConfiguration($this->configuration, [
            Yaml::parseFile(__DIR__ . '/../../config.yml'),
            Yaml::parseFile($configFilePath),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
