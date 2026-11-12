<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Utility;

/**
 * Normalises file paths for report output.
 */
final class ReportPath
{
    /**
     * Converts backslashes to slashes and strips leading "./" prefixes.
     * Absolute paths and dot-directories (".hidden/") are left intact.
     */
    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        while (str_starts_with($path, './')) {
            $path = substr($path, 2);
        }

        return $path;
    }
}
