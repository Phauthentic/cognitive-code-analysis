<?php

declare(strict_types=1);

namespace Phauthentic\CodeQuality\Config;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Definition\Processor;

class ConfigService
{
    private array $config;

    public function __construct(string $configFilePath)
    {
        $this->config = $this->loadConfig($configFilePath);
    }

    private function loadConfig(string $configFilePath): array
    {
        // Load the YAML configuration file
        $yamlConfig = Yaml::parseFile($configFilePath);

        // Create and process the configuration tree
        $processor = new Processor();
        $configuration = new ConfigLoader();
        return $processor->processConfiguration($configuration, [$yamlConfig]);
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
