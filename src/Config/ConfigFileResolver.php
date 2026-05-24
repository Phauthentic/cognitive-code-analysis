<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

class ConfigFileResolver
{
    public const DEFAULT_FILENAME = 'phpcca.yaml';

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
