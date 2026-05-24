<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

/**
 * Resolves the config file path from an explicit --config option or auto-discovery of cca.yaml.
 */
class ConfigFileResolver
{
    public const DEFAULT_FILENAME = 'cca.yaml';

    public function resolve(?string $explicitConfigPath): ?string
    {
        if ($explicitConfigPath !== null) {
            return $explicitConfigPath;
        }

        $defaultPath = getcwd() . DIRECTORY_SEPARATOR . self::DEFAULT_FILENAME;

        return is_file($defaultPath) ? $defaultPath : null;
    }

    public function getDefaultPath(): string
    {
        return getcwd() . DIRECTORY_SEPARATOR . self::DEFAULT_FILENAME;
    }
}
