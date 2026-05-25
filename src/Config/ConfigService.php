<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Definition\Processor;

class ConfigService
{
    private CognitiveConfig $config;

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function __construct(
        private readonly Processor $processor,
        private readonly ConfigLoader $configuration
    ) {
        $this->loadDefaultConfig();
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    private function loadDefaultConfig(): void
    {
        $defaultConfig = Yaml::parseFile(__DIR__ . '/../../phpcca.yaml');
        if (!is_array($defaultConfig)) {
            throw new ConfigException('Default configuration file is invalid.');
        }

        /** @var array<string, mixed> $config */
        $config = $this->processor->processConfiguration($this->configuration, [
            $defaultConfig,
        ]);

        $this->config = (new ConfigFactory())->fromArray($config);
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function loadConfig(string $configFilePath): void
    {
        $defaultConfig = Yaml::parseFile(__DIR__ . '/../../phpcca.yaml');
        $providedConfig = Yaml::parseFile($configFilePath);
        if (!is_array($defaultConfig) || !is_array($providedConfig)) {
            throw new ConfigException('Configuration file is invalid.');
        }

        /** @var array<string, mixed> $config */
        $config = $this->processor->processConfiguration($this->configuration, [
            $defaultConfig,
            $providedConfig,
        ]);

        $this->config = (new ConfigFactory())->fromArray($config);
    }

    public function getConfig(): CognitiveConfig
    {
        return $this->config;
    }
}
