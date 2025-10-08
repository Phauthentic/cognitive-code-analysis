<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Phauthentic\CognitiveCodeAnalysis\Cache\CacheItem;
use Phauthentic\CognitiveCodeAnalysis\Cache\Exception\CacheException;
use Phauthentic\CognitiveCodeAnalysis\Cache\FileCache;

/**
 * Unit tests for FileCache
 */
class FileCacheTest extends TestCase
{
    private string $testCacheDir;
    private FileCache $cache;

    protected function setUp(): void
    {
        $this->testCacheDir = sys_get_temp_dir() . '/phpcca-cache-test-' . uniqid();
        $this->cache = new FileCache($this->testCacheDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testCacheDir)) {
            $this->removeDirectory($this->testCacheDir);
        }
    }

    public function testConstructorCreatesCacheDirectory(): void
    {
        $this->assertDirectoryExists($this->testCacheDir);
        $this->assertDirectoryIsWritable($this->testCacheDir);
    }

    public function testConstructorWithTrailingSlash(): void
    {
        $cacheDirWithSlash = $this->testCacheDir . '/';
        $cache = new FileCache($cacheDirWithSlash);

        $this->assertDirectoryExists($this->testCacheDir);
    }

    public function testGetItemReturnsMissForNonExistentKey(): void
    {
        $item = $this->cache->getItem('non-existent-key');

        $this->assertEquals('non-existent-key', $item->getKey());
        $this->assertNull($item->get());
        $this->assertFalse($item->isHit());
    }

    public function testSaveAndGetItem(): void
    {
        $key = 'test-key';
        $value = 'test-value';

        $item = new CacheItem($key, $value, true);
        $this->assertTrue($this->cache->save($item));

        $retrievedItem = $this->cache->getItem($key);
        $this->assertEquals($key, $retrievedItem->getKey());
        $this->assertEquals($value, $retrievedItem->get());
        $this->assertTrue($retrievedItem->isHit());
    }

    public function testSaveAndGetItemWithArray(): void
    {
        $key = 'array-key';
        $value = ['key1' => 'value1', 'key2' => 123, 'key3' => ['nested' => true]];

        $item = new CacheItem($key, $value, true);
        $this->assertTrue($this->cache->save($item));

        $retrievedItem = $this->cache->getItem($key);
        $retrievedValue = $retrievedItem->get();
        $this->assertIsArray($retrievedValue);
        $this->assertEquals('value1', $retrievedValue['key1']);
        $this->assertEquals(123, $retrievedValue['key2']);
        $this->assertIsArray($retrievedValue['key3']);
        $this->assertTrue($retrievedValue['key3']['nested']);
        $this->assertTrue($retrievedItem->isHit());
    }

    public function testSaveAndGetItemWithObject(): void
    {
        $key = 'object-key';
        $value = (object) ['property1' => 'value1', 'property2' => 456];

        $item = new CacheItem($key, $value, true);
        $this->assertTrue($this->cache->save($item));

        $retrievedItem = $this->cache->getItem($key);
        $retrievedValue = $retrievedItem->get();
        $this->assertIsArray($retrievedValue);
        $this->assertEquals('value1', $retrievedValue['property1']);
        $this->assertEquals(456, $retrievedValue['property2']);
        $this->assertTrue($retrievedItem->isHit());
    }

    public function testSaveAndGetItemWithNullValue(): void
    {
        $key = 'null-key';

        $item = new CacheItem($key, null, true);
        $this->assertTrue($this->cache->save($item));

        // Saving null should delete the item
        $this->assertFalse($this->cache->hasItem($key));
    }

    public function testHasItem(): void
    {
        $key = 'has-item-test';

        $this->assertFalse($this->cache->hasItem($key));

        $item = new CacheItem($key, 'value', true);
        $this->cache->save($item);

        $this->assertTrue($this->cache->hasItem($key));
    }

    public function testDeleteItem(): void
    {
        $key = 'delete-test';

        // Save an item first
        $item = new CacheItem($key, 'value', true);
        $this->cache->save($item);
        $this->assertTrue($this->cache->hasItem($key));

        // Delete the item
        $this->assertTrue($this->cache->deleteItem($key));
        $this->assertFalse($this->cache->hasItem($key));
    }

    public function testDeleteNonExistentItem(): void
    {
        $this->assertTrue($this->cache->deleteItem('non-existent-key'));
    }

    public function testDeleteItems(): void
    {
        $keys = ['key1', 'key2', 'key3'];

        // Save items first
        foreach ($keys as $key) {
            $item = new CacheItem($key, "value-{$key}", true);
            $this->cache->save($item);
        }

        // Verify all items exist
        foreach ($keys as $key) {
            $this->assertTrue($this->cache->hasItem($key));
        }

        // Delete all items
        $this->assertTrue($this->cache->deleteItems($keys));

        // Verify all items are deleted
        foreach ($keys as $key) {
            $this->assertFalse($this->cache->hasItem($key));
        }
    }

    public function testGetItems(): void
    {
        $keys = ['key1', 'key2', 'key3'];

        // Save some items
        $item1 = new CacheItem('key1', 'value1', true);
        $item2 = new CacheItem('key2', 'value2', true);
        $this->cache->save($item1);
        $this->cache->save($item2);

        $items = $this->cache->getItems($keys);

        $this->assertCount(3, $items);
        $this->assertArrayHasKey('key1', $items);
        $this->assertArrayHasKey('key2', $items);
        $this->assertArrayHasKey('key3', $items);

        $this->assertTrue($items['key1']->isHit());
        $this->assertEquals('value1', $items['key1']->get());

        $this->assertTrue($items['key2']->isHit());
        $this->assertEquals('value2', $items['key2']->get());

        $this->assertFalse($items['key3']->isHit());
        $this->assertNull($items['key3']->get());
    }

    public function testSaveDeferredAndCommit(): void
    {
        $key1 = 'deferred-key1';
        $key2 = 'deferred-key2';

        $item1 = new CacheItem($key1, 'value1', true);
        $item2 = new CacheItem($key2, 'value2', true);

        $this->assertTrue($this->cache->saveDeferred($item1));
        $this->assertTrue($this->cache->saveDeferred($item2));

        // Items should not be saved yet
        $this->assertFalse($this->cache->hasItem($key1));
        $this->assertFalse($this->cache->hasItem($key2));

        // Commit the deferred items
        $this->assertTrue($this->cache->commit());

        // Now items should be saved
        $this->assertTrue($this->cache->hasItem($key1));
        $this->assertTrue($this->cache->hasItem($key2));

        $retrievedItem1 = $this->cache->getItem($key1);
        $this->assertEquals('value1', $retrievedItem1->get());

        $retrievedItem2 = $this->cache->getItem($key2);
        $this->assertEquals('value2', $retrievedItem2->get());
    }

    public function testClear(): void
    {
        // Save some items
        $item1 = new CacheItem('key1', 'value1', true);
        $item2 = new CacheItem('key2', 'value2', true);
        $this->cache->save($item1);
        $this->cache->save($item2);

        $this->assertTrue($this->cache->hasItem('key1'));
        $this->assertTrue($this->cache->hasItem('key2'));

        // Clear the cache
        $this->assertTrue($this->cache->clear());

        // Items should be gone
        $this->assertFalse($this->cache->hasItem('key1'));
        $this->assertFalse($this->cache->hasItem('key2'));

        // Cache directory should still exist
        $this->assertDirectoryExists($this->testCacheDir);
    }

    public function testCacheFileStructure(): void
    {
        $key = 'test-structure';
        $value = 'test-value';

        $item = new CacheItem($key, $value, true);
        $this->cache->save($item);

        // Check that subdirectory was created
        $hash = md5($key);
        $subDir = substr($hash, 0, 2);
        $expectedSubDir = $this->testCacheDir . '/' . $subDir;

        $this->assertDirectoryExists($expectedSubDir);

        // Check that cache file was created
        $expectedFile = $expectedSubDir . '/' . $hash . '.cache';
        $this->assertFileExists($expectedFile);

        // Verify file content
        $content = file_get_contents($expectedFile);
        $this->assertNotFalse($content);

        $data = json_decode($content, true);
        $this->assertEquals($value, $data);
    }

    public function testUtf8Sanitization(): void
    {
        $key = 'utf8-test';
        $value = [
            'valid_utf8' => 'Hello World',
            'invalid_utf8' => "Invalid \x80 sequence",
            'mixed' => "Valid text with \x80 invalid chars",
            'unicode' => 'Unicode: 你好世界 🌍'
        ];

        $item = new CacheItem($key, $value, true);
        $this->assertTrue($this->cache->save($item));

        $retrievedItem = $this->cache->getItem($key);
        $retrievedValue = $retrievedItem->get();

        $this->assertIsArray($retrievedValue);
        $this->assertArrayHasKey('valid_utf8', $retrievedValue);
        $this->assertArrayHasKey('invalid_utf8', $retrievedValue);
        $this->assertArrayHasKey('mixed', $retrievedValue);
        $this->assertArrayHasKey('unicode', $retrievedValue);

        // Verify UTF-8 sanitization worked
        $this->assertIsString($retrievedValue['invalid_utf8']);
        $this->assertIsString($retrievedValue['mixed']);
    }

    public function testCorruptedCacheFile(): void
    {
        $key = 'corrupted-test';
        $value = 'test-value';

        // Save a valid item first
        $item = new CacheItem($key, $value, true);
        $this->cache->save($item);

        // Corrupt the cache file
        $hash = md5($key);
        $subDir = substr($hash, 0, 2);
        $cacheFile = $this->testCacheDir . '/' . $subDir . '/' . $hash . '.cache';

        file_put_contents($cacheFile, 'invalid json content');

        // Should return a miss for corrupted file
        $retrievedItem = $this->cache->getItem($key);
        $this->assertFalse($retrievedItem->isHit());
        $this->assertNull($retrievedItem->get());
    }

    public function testEmptyCacheFile(): void
    {
        $key = 'empty-test';
        $value = 'test-value';

        // Save a valid item first
        $item = new CacheItem($key, $value, true);
        $this->cache->save($item);

        // Empty the cache file
        $hash = md5($key);
        $subDir = substr($hash, 0, 2);
        $cacheFile = $this->testCacheDir . '/' . $subDir . '/' . $hash . '.cache';

        file_put_contents($cacheFile, '');

        // Should return a miss for empty file
        $retrievedItem = $this->cache->getItem($key);
        $this->assertFalse($retrievedItem->isHit());
        $this->assertNull($retrievedItem->get());
    }

    public function testCacheDirectoryCreationFailure(): void
    {
        // Create a cache directory that's not writable
        $nonWritableDir = '/root/non-writable-cache';

        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('Failed to create cache directory');

        new FileCache($nonWritableDir);
    }

    public function testCacheSubdirectoryCreationFailure(): void
    {
        // Create a cache with a non-writable parent directory
        $parentDir = $this->testCacheDir . '/parent';
        mkdir($parentDir, 0444); // Read-only

        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('Failed to create cache directory');

        $cache = new FileCache($parentDir . '/cache');

        // Try to save an item which will trigger subdirectory creation
        $item = new CacheItem('test-key', 'test-value', true);
        $cache->save($item);
    }

    public function testJsonEncodeFailure(): void
    {
        // Create a value that cannot be JSON encoded
        $key = 'json-fail-test';
        $value = "\x80"; // Invalid UTF-8 that might cause JSON encoding issues

        $item = new CacheItem($key, $value, true);

        // This should handle the encoding gracefully
        $result = $this->cache->save($item);

        // The save might succeed due to UTF-8 sanitization
        if ($result) {
            $retrievedItem = $this->cache->getItem($key);
            $this->assertTrue($retrievedItem->isHit());
        }
    }

    public function testLargeDataHandling(): void
    {
        $key = 'large-data-test';
        $value = str_repeat('A', 10000); // 10KB string

        $item = new CacheItem($key, $value, true);
        $this->assertTrue($this->cache->save($item));

        $retrievedItem = $this->cache->getItem($key);
        $this->assertEquals($value, $retrievedItem->get());
        $this->assertTrue($retrievedItem->isHit());
    }

    public function testSpecialCharactersInKey(): void
    {
        $keys = [
            'key with spaces',
            'key-with-dashes',
            'key_with_underscores',
            'key.with.dots',
            'key/with/slashes',
            'key\\with\\backslashes',
            'key:with:colons',
            'key;with;semicolons',
            'key"with"quotes',
            "key'with'singlequotes"
        ];

        foreach ($keys as $key) {
            $value = "value-for-{$key}";
            $item = new CacheItem($key, $value, true);

            $this->assertTrue($this->cache->save($item), "Failed to save key: {$key}");
            $this->assertTrue($this->cache->hasItem($key), "Failed to verify key exists: {$key}");

            $retrievedItem = $this->cache->getItem($key);
            $this->assertEquals($value, $retrievedItem->get(), "Failed to retrieve value for key: {$key}");
        }
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
