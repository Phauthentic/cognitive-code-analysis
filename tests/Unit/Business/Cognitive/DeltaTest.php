<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business\Cognitive;

use Phauthentic\CodeQualityMetrics\Business\Cognitive\Delta;
use PHPUnit\Framework\TestCase;

/**
 * Test case for the Delta class.
 */
class DeltaTest extends TestCase
{
    public function testDeltaWhenIncreased(): void
    {
        $before = 5.0;
        $after = 10.0;

        $delta = new Delta($before, $after);

        $this->assertTrue($delta->hasIncreased());
        $this->assertSame(5.0, $delta->getValue());
    }

    public function testDeltaWhenDecreased(): void
    {
        $before = 10.0;
        $after = 5.0;

        $delta = new Delta($before, $after);

        $this->assertFalse($delta->hasIncreased());
        $this->assertSame(-5.0, $delta->getValue());
    }

    public function testDeltaWhenEqual(): void
    {
        $before = 10.0;
        $after = 10.0;

        $delta = new Delta($before, $after);

        $this->assertFalse($delta->hasIncreased());
        $this->assertSame(0.0, $delta->getValue());
    }
}
