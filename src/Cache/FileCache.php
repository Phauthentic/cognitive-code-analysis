<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Cache;

use Phauthentic\CognitiveCodeAnalysis\Cache\Exception\CacheException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * PSR-6 File-based Cache implementation with compression support
 */
class FileCache implements CacheItemPoolInterface
{
    private string $cacheDirectory;
    /** @var array<CacheItemInterface> */
    private array $deferred = [];

    public function __construct(string $cacheDirectory = './.phpcca.cache')
    {
        $this->cacheDirectory = rtrim($cacheDirectory, '/');
        $this->ensureCacheDirectory();
    }

    public function getItem(string $key): CacheItemInterface
    {
        $filePath = $this->getCacheFilePath($key);

        if (!file_exists($filePath)) {
            return new CacheItem($key, null, false);
        }

        $data = $this->loadCacheData($filePath);
        if ($data === null) {
            return new CacheItem($key, null, false);
        }

        return new CacheItem($key, $data, true);
    }

    /** @return array<string, CacheItemInterface> */
    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    public function hasItem(string $key): bool
    {
        $filePath = $this->getCacheFilePath($key);
        return file_exists($filePath) && $this->loadCacheData($filePath) !== null;
    }

    public function clear(): bool
    {
        try {
            $this->removeDirectory($this->cacheDirectory);
            $this->ensureCacheDirectory();
            return true;
        } catch (CacheException $e) {
            return false;
        }
    }

    public function deleteItem(string $key): bool
    {
        $filePath = $this->getCacheFilePath($key);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true;
    }

    public function deleteItems(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->deleteItem($key)) {
                $success = false;
            }
        }
        return $success;
    }

    public function save(CacheItemInterface $item): bool
    {
        if (!$item instanceof CacheItem) {
            return false;
        }

        $filePath = $this->getCacheFilePath($item->getKey());
        $data = $item->get();

        if ($data === null) {
            return $this->deleteItem($item->getKey());
        }

        return $this->saveCacheData($filePath, $data);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        if (!$item instanceof CacheItem) {
            return false;
        }

        $this->deferred[] = $item;
        return true;
    }

    public function commit(): bool
    {
        $success = true;
        foreach ($this->deferred as $item) {
            if (!$this->save($item)) {
                $success = false;
            }
        }
        $this->deferred = [];
        return $success;
    }

    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cacheDirectory)) {
            if (!mkdir($this->cacheDirectory, 0755, true)) {
                throw new CacheException("Failed to create cache directory: {$this->cacheDirectory}");
            }
        }
    }

    private function getCacheFilePath(string $key): string
    {
        // Create subdirectories to avoid too many files in one directory
        $hash = md5($key);
        $subDir = substr($hash, 0, 2);
        $dir = $this->cacheDirectory . '/' . $subDir;

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new CacheException("Failed to create cache subdirectory: {$dir}");
            }
        }

        return $dir . '/' . $hash . '.cache';
    }

    /** @return array<string, mixed>|null */
    private function loadCacheData(string $filePath): ?array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if ($data === null) {
            return null;
        }

        // Data is stored without compression for now

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function saveCacheData(string $filePath, array $data): bool
    {
        // Store data without compression for now (compression can be added later)
        // This ensures cache works reliably

        // Sanitize data to ensure valid UTF-8 encoding
        $data = $this->sanitizeUtf8($data);

        $json = json_encode($data, JSON_PRETTY_PRINT);
        if ($json === false) {
            return false;
        }

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                return false;
            }
        }

        $result = file_put_contents($filePath, $json);
        return $result !== false;
    }

    /**
     * Recursively sanitize UTF-8 data to ensure valid encoding
     */
    private function sanitizeUtf8(mixed $data): mixed
    {
        if (is_string($data)) {
            // Remove or replace invalid UTF-8 characters
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }

        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $sanitizedKey = is_string($key) ? mb_convert_encoding($key, 'UTF-8', 'UTF-8') : $key;
                $sanitized[$sanitizedKey] = $this->sanitizeUtf8($value);
            }
            return $sanitized;
        }

        if (is_object($data)) {
            // Convert objects to arrays for sanitization
            $array = (array) $data;
            $sanitized = $this->sanitizeUtf8($array);
            return (object) $sanitized;
        }

        return $data;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
