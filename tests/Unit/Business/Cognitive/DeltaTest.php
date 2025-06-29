<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Delta;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the Delta class.
 */
class DeltaTest extends TestCase
{
    #[Test]
    public function testDeltaWhenIncreased(): void
    {
        $before = 5.0;
        $after = 10.0;

        $delta = new Delta($before, $after);

        $this->assertTrue($delta->hasIncreased());
        $this->assertSame(5.0, $delta->getValue());
    }

    #[Test]
    public function testDeltaWhenDecreased(): void
    {
        $before = 10.0;
        $after = 5.0;

        $delta = new Delta($before, $after);

        $this->assertFalse($delta->hasIncreased());
        $this->assertSame(-5.0, $delta->getValue());
    }

    #[Test]
    public function testDeltaWhenEqual(): void
    {
        $before = 10.0;
        $after = 10.0;

        $delta = new Delta($before, $after);

        $this->assertFalse($delta->hasIncreased());
        $this->assertSame(0.0, $delta->getValue());
    }

    #[Test]
    public function testDeltaHasChanged(): void
    {
        $before = 10.0;
        $after = 2.0;

        $delta = new Delta($before, $after);
        $this->assertFalse($delta->hasNotChanged());
    }

    #[Test]
    public function testDeltaHasNotChanged(): void
    {
        $before = 10.0;
        $after = 10.0;

        $delta = new Delta($before, $after);
        $this->assertTrue($delta->hasNotChanged());
    }

    #[Test]
    public function testDeltaToString(): void
    {
        $before = 10.0;
        $after = 5.0;

        $delta = new Delta($before, $after);
        $this->assertSame('-5', (string)$delta);
    }
}
