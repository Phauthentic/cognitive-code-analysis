<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business\Halstead;

use Phauthentic\CodeQualityMetrics\Business\DirectoryScanner;
use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollector;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class HalsteadMetricsCollectorTest extends TestCase
{
    public function testCount()
    {
        $collector = new HalsteadMetricsCollector(
            new ParserFactory(),
            new NodeTraverser(),
            new DirectoryScanner()
        );
        $collection = $collector->collect('./tests/TestCode');

        $this->assertCount(4, $collection);
    }
}
