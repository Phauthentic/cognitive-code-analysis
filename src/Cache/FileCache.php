<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Cache;

use Phauthentic\CognitiveCodeAnalysis\Cache\Exception\CacheException;
use Closure;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * PSR-6 File-based Cache implementation
 */
class FileCache implements CacheItemPoolInterface
{
    private string $cacheDirectory;

    /** @var (Closure(): string)|null */
    private ?Closure $directoryResolver = null;

    /**
     * @var array<CacheItemInterface>
     */
    private array $deferred = [];

    /**
     * @throws CacheException
     */
    public function __construct(string $cacheDirectory = './.phpcca.cache')
    {
        $this->cacheDirectory = rtrim($cacheDirectory, '/');
    }

    /**
     * Resolve the cache directory on first use instead of using the constructor value.
     * Useful when the directory comes from configuration that is loaded after construction.
     *
     * @param Closure(): string $resolver
     */
    public function setDirectoryResolver(Closure $resolver): void
    {
        $this->directoryResolver = $resolver;
    }

    private function getCacheDirectory(): string
    {
        if ($this->directoryResolver !== null) {
            $this->cacheDirectory = rtrim(($this->directoryResolver)(), '/');
            $this->directoryResolver = null;
        }

        return $this->cacheDirectory;
    }

    /**
     * @throws CacheException
     */
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

    /**
     * @return array<string, CacheItemInterface>
     * @throws CacheException
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    /**
     * @throws CacheException
     */
    public function hasItem(string $key): bool
    {
        $filePath = $this->getCacheFilePath($key);

        return file_exists($filePath) && $this->loadCacheData($filePath) !== null;
    }

    public function clear(): bool
    {
        try {
            $this->removeDirectory($this->getCacheDirectory());
            return true;
        } catch (CacheException $e) {
            return false;
        }
    }

    /**
     * @throws CacheException
     */
    public function deleteItem(string $key): bool
    {
        $filePath = $this->getCacheFilePath($key);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return true;
    }

    /**
     * @throws CacheException
     */
    public function deleteItems(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if ($this->deleteItem($key)) {
                continue;
            }

            $success = false;
        }
        return $success;
    }

    /**
     * @throws CacheException
     */
    public function save(CacheItemInterface $item): bool
    {
        $filePath = $this->getCacheFilePath($item->getKey());
        $data = $item->get();

        if ($data === null) {
            return $this->deleteItem($item->getKey());
        }

        return $this->saveCacheData($filePath, $data);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[] = $item;

        return true;
    }

    public function commit(): bool
    {
        $success = true;
        foreach ($this->deferred as $item) {
            if ($this->save($item)) {
                continue;
            }

            $success = false;
        }
        $this->deferred = [];
        return $success;
    }

    /**
     * @SuppressWarnings("PHPMD.ErrorControlOperator")
     * @throws CacheException
     */
    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new CacheException("Failed to create cache directory: {$dir}");
        }
    }

    /**
     * Subdirectories avoid too many files in one directory; they are created on write.
     */
    private function getCacheFilePath(string $key): string
    {
        $hash = md5($key);
        $subDir = substr($hash, 0, 2);

        return $this->getCacheDirectory() . '/' . $subDir . '/' . $hash . '.cache';
    }

    private function loadCacheData(string $filePath): mixed
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

            $data = json_decode($content, true);
        if ($data === null) {
            return null;
        }

        return $data;
    }

    /**
     * Store data
     *
     * Sanitize data to ensure valid UTF-8 encoding
     *
     * @param mixed $data
     * @throws CacheException
     */
    private function saveCacheData(string $filePath, mixed $data): bool
    {
        $data = $this->sanitizeUtf8($data);

        $json = json_encode($data, JSON_PRETTY_PRINT);
        if ($json === false) {
            return false;
        }

        $this->ensureDirectory(dirname($filePath));

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

        $scanResult = scandir($dir);
        if ($scanResult === false) {
            return;
        }

        $files = array_diff($scanResult, ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }
            unlink($path);
        }

        rmdir($dir);
    }
}
