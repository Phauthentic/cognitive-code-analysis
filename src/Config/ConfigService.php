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
    }

    public function loadConfig(string $configFilePath): void
    {
        $yamlConfig = Yaml::parseFile($configFilePath);

        $this->config = $this->processor->processConfiguration($this->configuration, [$yamlConfig]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
