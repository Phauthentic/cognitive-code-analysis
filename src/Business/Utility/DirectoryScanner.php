<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Utility;

use FilesystemIterator;
use Generator;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Class DirectoryScanner
 *
 * Scans directories and collects files as SplFileInfo objects using generators.
 */
class DirectoryScanner
{
    /**
     * Scan files and directories.
     *
     * @param array<string> $paths Array of file or directory paths to scan
     * @param array<string> $exclude Array of regex patterns to exclude files
     * @return Generator<SplFileInfo> Generator yielding SplFileInfo objects
     * @throws CognitiveAnalysisException
     */
    public function scan(array $paths, array $exclude = []): Generator
    {
        foreach ($paths as $path) {
            $this->assertValidPath($path);

            if (is_file($path)) {
                yield from $this->yieldFileIfNotExcluded($path, $exclude);
            }

            if (!is_dir($path)) {
                continue;
            }

            yield from $this->traverseDirectory($path, $exclude);
        }
    }

    /**
     * @throws CognitiveAnalysisException
     */
    private function assertValidPath(string $path): void
    {
        if (!file_exists($path)) {
            throw new CognitiveAnalysisException("Path does not exist: $path");
        }
    }

    /**
     * @param string $path
     * @param array<string> $exclude Array of regex patterns to exclude files
     * @return Generator<SplFileInfo>
     */
    private function yieldFileIfNotExcluded(string $path, array $exclude): Generator
    {
        $fileInfo = new SplFileInfo($path);

        if ($this->isExcluded($fileInfo, $exclude)) {
            return;
        }

        yield $fileInfo;
    }

    /**
     * Traverse a directory and yield files as SplFileInfo objects,
     * applying exclusion filters on the fly.
     *
     * @param string $directory Directory path to traverse
     * @param array<string> $exclude Array of regex patterns to exclude files
     * @return Generator<SplFileInfo> Generator yielding SplFileInfo objects
     */
    private function traverseDirectory(string $directory, array $exclude): Generator
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $files = $this->getFilesFromIterator($iterator, $exclude);
        $this->sortFilesByPathname($files);

        // Yield sorted files
        foreach ($files as $fileInfo) {
            yield $fileInfo;
        }
    }

    /**
     * Sort files by their pathname to ensure consistent order across platforms.
     *
     * @param array<SplFileInfo> $files Array of SplFileInfo objects to sort
     */
    private function sortFilesByPathname(array &$files): void
    {
        usort($files, function (SplFileInfo $alpha, SplFileInfo $beta) {
            return strcmp($alpha->getPathname(), $beta->getPathname());
        });
    }

    /**
     * Collect files from an iterator, applying exclusion filters.
     *
     * @param \Iterator<SplFileInfo> $iterator Iterator to collect files from
     * @param array<string> $exclude Array of regex patterns to exclude files
     * @return array<SplFileInfo> Array of SplFileInfo objects
     */
    private function getFilesFromIterator(\Iterator $iterator, array $exclude): array
    {
        $files = [];
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || $this->isExcluded($fileInfo, $exclude)) {
                continue;
            }

            $files[] = $fileInfo;
        }

        return $files;
    }

    /**
     * Check if a file should be excluded based on exclusion patterns.
     *
     * @param SplFileInfo $fileInfo File information object
     * @param array<string> $exclude Array of regex patterns to exclude files
     * @return bool True if the file should be excluded, false otherwise
     */
    private function isExcluded(SplFileInfo $fileInfo, array $exclude): bool
    {
        $exclude[] =  '^(?!.*\.php$).+';

        foreach ($exclude as $pattern) {
            if (preg_match('/' . $pattern . '/', $fileInfo->getPathname())) {
                return true;
            }
        }

        return false;
    }
}
