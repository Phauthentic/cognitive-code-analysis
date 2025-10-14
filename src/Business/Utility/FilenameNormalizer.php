<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Utility;

use SplFileInfo;

/**
 * Utility class for normalizing filenames, especially for test environments.
 */
class FilenameNormalizer
{
    /**
     * Normalize filename for the test environment
     *
     * This is to ensure consistent file paths in test outputs
     *
     * @param SplFileInfo $file The file to normalize
     * @return string The normalized filename
     */
    public static function normalize(SplFileInfo $file): string
    {
        $filename = $file->getRealPath();

        if (getenv('APP_ENV') !== 'test') {
            return $filename;
        }

        $projectRoot = self::getProjectRoot();
        if ($projectRoot && str_starts_with($filename, $projectRoot)) {
            $filename = substr($filename, strlen($projectRoot) + 1);
        }

        return $filename;
    }

    /**
     * Get the project root directory by traversing up from the current directory
     * until composer.json is found.
     *
     * Start from the current file's directory and traverse up to find composer.json
     *
     * @return string|null The project root path or null if not found
     */
    private static function getProjectRoot(): ?string
    {
        $currentDir = __DIR__;

        while ($currentDir !== dirname($currentDir)) {
            if (file_exists($currentDir . DIRECTORY_SEPARATOR . 'composer.json')) {
                return $currentDir;
            }
            $currentDir = dirname($currentDir);
        }

        return null;
    }
}
