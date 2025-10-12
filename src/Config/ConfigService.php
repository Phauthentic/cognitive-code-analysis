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
        $config = $this->processor->processConfiguration($this->configuration, [
            Yaml::parseFile(__DIR__ . '/../../config.yml'),
        ]);

        $this->config = (new ConfigFactory())->fromArray($config);
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function loadConfig(string $configFilePath): void
    {
        $config = $this->processor->processConfiguration($this->configuration, [
            Yaml::parseFile(__DIR__ . '/../../config.yml'),
            Yaml::parseFile($configFilePath),
        ]);

        $this->config = (new ConfigFactory())->fromArray($config);
    }

    public function getConfig(): CognitiveConfig
    {
        return $this->config;
    }
}
