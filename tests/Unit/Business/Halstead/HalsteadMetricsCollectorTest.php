<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business\Halstead;

use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollector;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class HalsteadMetricsCollectorTest extends TestCase
{
    public function testCount()
    {
        $collector = new HalsteadMetricsCollector();
        $collection = $collector->collect('./tests/TestCode');

        $this->assertCount(4, $collection);
    }
}
