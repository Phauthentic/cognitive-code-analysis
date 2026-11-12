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

        // Keep only the path names: an SplFileInfo object takes about 8 KB of memory,
        // so holding one per file for a large code base would cost tens of megabytes.
        $pathnames = $this->getPathnamesFromIterator($iterator, $exclude);

        // Sort to ensure a consistent order across platforms
        sort($pathnames, SORT_STRING);

        foreach ($pathnames as $pathname) {
            yield new SplFileInfo($pathname);
        }
    }

    /**
     * Collect the path names of the files from an iterator, applying exclusion filters.
     *
     * @param \Iterator<SplFileInfo> $iterator Iterator to collect files from
     * @param array<string> $exclude Array of regex patterns to exclude files
     * @return array<string> Path names of the files
     */
    private function getPathnamesFromIterator(\Iterator $iterator, array $exclude): array
    {
        $pathnames = [];
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile() || $this->isExcluded($fileInfo, $exclude)) {
                continue;
            }

            $pathnames[] = $fileInfo->getPathname();
        }

        return $pathnames;
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
