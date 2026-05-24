<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class ConfigInitializer
{
    public function __construct(
        private readonly Processor $processor,
        private readonly ConfigLoader $configLoader,
        private readonly string $bundledConfigPath,
    ) {
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public function createDefaultConfig(array $overrides = []): array
    {
        $defaultConfig = Yaml::parseFile($this->bundledConfigPath);
        if (!is_array($defaultConfig)) {
            throw new ConfigException(
                sprintf('Bundled configuration file is invalid: %s', $this->bundledConfigPath)
            );
        }

        if ($overrides !== []) {
            $defaultConfig = array_replace_recursive($defaultConfig, $overrides);
        }

        /** @var array<string, mixed> $config */
        $config = $this->processor->processConfiguration($this->configLoader, [$defaultConfig]);

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function writeConfigFile(string $path, array $config): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new ConfigException("Failed to create directory: {$directory}");
        }

        $yaml = Yaml::dump($config, 4, 2);
        if (file_put_contents($path, $yaml) === false) {
            throw new ConfigException("Failed to write config file: {$path}");
        }
    }
}
