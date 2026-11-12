<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Cache;

/**
 * Deletes the files FileCache writes (`<2 hex>/<32 hex>.cache`) from a cache directory.
 *
 * Directories are removed only when they are empty afterwards. The cache directory comes from user
 * configuration, so a misconfigured `cache.directory` (for example `.` or `src`) must never lose
 * unrelated files.
 */
final class CacheDirectoryCleaner
{
    /**
     * @return bool False when at least one cache file could not be deleted
     */
    public function clear(string $directory): bool
    {
        if (!is_dir($directory)) {
            return true;
        }

        $success = true;
        foreach ($this->listEntries($directory, '/^[0-9a-f]{2}$/') as $subDirectory) {
            if (!is_dir($subDirectory)) {
                continue;
            }

            foreach ($this->listEntries($subDirectory, '/^[0-9a-f]{32}\.cache$/') as $file) {
                $success = $this->deleteFile($file) && $success;
            }

            $this->removeDirectoryIfEmpty($subDirectory);
        }

        $this->removeDirectoryIfEmpty($directory);

        return $success;
    }

    /**
     * @return array<string> Paths of the entries in $directory whose name matches $pattern
     */
    private function listEntries(string $directory, string $pattern): array
    {
        /** @var array<string> $entries */
        $entries = preg_grep($pattern, scandir($directory) ?: []) ?: [];

        return array_map(static fn (string $entry): string => $directory . '/' . $entry, $entries);
    }

    /**
     * Writability is checked first so that a failed deletion is reported instead of raising a warning.
     */
    private function deleteFile(string $file): bool
    {
        return is_file($file) && is_writable(dirname($file)) && unlink($file);
    }

    private function removeDirectoryIfEmpty(string $directory): void
    {
        if (count(scandir($directory) ?: []) !== 2 || !is_writable(dirname($directory))) {
            return;
        }

        rmdir($directory);
    }
}
