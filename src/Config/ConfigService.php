<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

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

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function __construct(
        private readonly Processor $processor,
        private readonly ConfigLoader $configuration
    ) {
        $this->config = $this->processor->processConfiguration($this->configuration, [
            Yaml::parseFile(__DIR__ . '/../../config.yml'),
        ]);
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function loadConfig(string $configFilePath): void
    {
        $this->config = $this->processor->processConfiguration($this->configuration, [
            Yaml::parseFile(__DIR__ . '/../../config.yml'),
            Yaml::parseFile($configFilePath),
        ]);
    }

    public function getConfig(): CognitiveConfig
    {
        return (new ConfigFactory())->fromArray($this->config);
    }
}
