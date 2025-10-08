<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Cache\Exception;

use PHPUnit\Framework\TestCase;
use Phauthentic\CognitiveCodeAnalysis\Cache\Exception\CacheException;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Unit tests for CacheException
 */
class CacheExceptionTest extends TestCase
{
    public function testInheritsFromCognitiveAnalysisException(): void
    {
        $exception = new CacheException();

        $this->assertInstanceOf(CognitiveAnalysisException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testDefaultConstructor(): void
    {
        $exception = new CacheException();

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithMessage(): void
    {
        $message = 'Cache operation failed';
        $exception = new CacheException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithMessageAndCode(): void
    {
        $message = 'Cache operation failed';
        $code = 500;
        $exception = new CacheException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithMessageCodeAndPrevious(): void
    {
        $message = 'Cache operation failed';
        $code = 500;
        $previous = new \RuntimeException('Previous exception');
        $exception = new CacheException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testCanBeThrownAndCaught(): void
    {
        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('Test exception message');

        throw new CacheException('Test exception message');
    }

    public function testCanBeCaughtAsParentException(): void
    {
        $caught = false;

        try {
            throw new CacheException('Test message');
        } catch (CognitiveAnalysisException $e) {
            $caught = true;
            $this->assertEquals('Test message', $e->getMessage());
        }

        $this->assertTrue($caught);
    }

    public function testCanBeCaughtAsGenericException(): void
    {
        $caught = false;

        try {
            throw new CacheException('Test message');
        } catch (\Exception $e) {
            $caught = true;
            $this->assertEquals('Test message', $e->getMessage());
        }

        $this->assertTrue($caught);
    }

    public function testExceptionWithSpecialCharacters(): void
    {
        $message = 'Cache failed: "Invalid UTF-8 sequence \x80"';
        $exception = new CacheException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionWithEmptyMessage(): void
    {
        $exception = new CacheException('');

        $this->assertEquals('', $exception->getMessage());
    }

    public function testExceptionWithVeryLongMessage(): void
    {
        $message = str_repeat('A', 1000);
        $exception = new CacheException($message);

        $this->assertEquals($message, $exception->getMessage());
    }
}
