<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Phauthentic\CognitiveCodeAnalysis\Cache\CacheItem;

/**
 * Unit tests for CacheItem
 */
class CacheItemTest extends TestCase
{
    public function testConstructorWithHit(): void
    {
        $key = 'test-key';
        $value = 'test-value';
        $isHit = true;

        $item = new CacheItem($key, $value, $isHit);

        $this->assertEquals($key, $item->getKey());
        $this->assertEquals($value, $item->get());
        $this->assertTrue($item->isHit());
    }

    public function testConstructorWithMiss(): void
    {
        $key = 'test-key';
        $value = null;
        $isHit = false;

        $item = new CacheItem($key, $value, $isHit);

        $this->assertEquals($key, $item->getKey());
        $this->assertNull($item->get());
        $this->assertFalse($item->isHit());
    }

    public function testSetValue(): void
    {
        $item = new CacheItem('test-key', 'initial-value', true);
        
        $newValue = 'new-value';
        $result = $item->set($newValue);

        $this->assertSame($item, $result);
        $this->assertEquals($newValue, $item->get());
    }

    public function testSetWithDifferentTypes(): void
    {
        $item = new CacheItem('test-key', null, false);

        // Test with string
        $item->set('string-value');
        $this->assertEquals('string-value', $item->get());

        // Test with array
        $arrayValue = ['key' => 'value', 'number' => 123];
        $item->set($arrayValue);
        $this->assertEquals($arrayValue, $item->get());

        // Test with object
        $objectValue = (object) ['property' => 'value'];
        $item->set($objectValue);
        $this->assertEquals($objectValue, $item->get());

        // Test with boolean
        $item->set(true);
        $this->assertTrue($item->get());

        // Test with integer
        $item->set(42);
        $this->assertEquals(42, $item->get());

        // Test with float
        $item->set(3.14);
        $this->assertEquals(3.14, $item->get());
    }

    public function testSetExpirationReturnsSelf(): void
    {
        $item = new CacheItem('test-key', 'value', true);
        
        $result = $item->setExpiration(3600);
        
        $this->assertSame($item, $result);
    }

    public function testGetExpirationReturnsNull(): void
    {
        $item = new CacheItem('test-key', 'value', true);
        
        $this->assertNull($item->getExpiration());
    }

    public function testExpiresAtReturnsSelf(): void
    {
        $item = new CacheItem('test-key', 'value', true);
        $expiration = new \DateTime('+1 hour');
        
        $result = $item->expiresAt($expiration);
        
        $this->assertSame($item, $result);
    }

    public function testExpiresAtWithNull(): void
    {
        $item = new CacheItem('test-key', 'value', true);
        
        $result = $item->expiresAt(null);
        
        $this->assertSame($item, $result);
    }

    public function testExpiresAfterWithInteger(): void
    {
        $item = new CacheItem('test-key', 'value', true);
        
        $result = $item->expiresAfter(3600);
        
        $this->assertSame($item, $result);
    }

    public function testExpiresAfterWithDateInterval(): void
    {
        $item = new CacheItem('test-key', 'value', true);
        $interval = new \DateInterval('PT1H');
        
        $result = $item->expiresAfter($interval);
        
        $this->assertSame($item, $result);
    }

    public function testExpiresAfterWithNull(): void
    {
        $item = new CacheItem('test-key', 'value', true);
        
        $result = $item->expiresAfter(null);
        
        $this->assertSame($item, $result);
    }

    public function testKeyIsImmutable(): void
    {
        $key = 'original-key';
        $item = new CacheItem($key, 'value', true);

        // The key should remain the same throughout the item's lifecycle
        $this->assertEquals($key, $item->getKey());
        
        $item->set('new-value');
        $this->assertEquals($key, $item->getKey());
        
        $item->setExpiration(3600);
        $this->assertEquals($key, $item->getKey());
    }

    public function testIsHitIsImmutable(): void
    {
        $item = new CacheItem('test-key', 'value', true);
        
        // isHit should remain true
        $this->assertTrue($item->isHit());
        
        $item->set('new-value');
        $this->assertTrue($item->isHit());
        
        $item->setExpiration(3600);
        $this->assertTrue($item->isHit());
    }

    public function testIsHitIsImmutableForMiss(): void
    {
        $item = new CacheItem('test-key', null, false);
        
        // isHit should remain false
        $this->assertFalse($item->isHit());
        
        $item->set('new-value');
        $this->assertFalse($item->isHit());
        
        $item->setExpiration(3600);
        $this->assertFalse($item->isHit());
    }
}
