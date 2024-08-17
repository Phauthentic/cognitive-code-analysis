<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business\Halstead;

use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetrics;
use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollection;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class HalsteadMetricsCollectionTest extends TestCase
{
    public function testAddAndCount(): void
    {
        $collection = new HalsteadMetricsCollection();
        $metric = new HalsteadMetrics([
            'n1' => 5,
            'n2' => 3,
            'N1' => 10,
            'N2' => 7,
        ]);

        $this->assertCount(0, $collection);

        $collection->add($metric);

        $this->assertCount(1, $collection);
    }

    public function testGetIterator(): void
    {
        $collection = new HalsteadMetricsCollection();
        $metric1 = new HalsteadMetrics([
            'n1' => 5,
            'n2' => 3,
            'N1' => 10,
            'N2' => 7,
        ]);
        $metric2 = new HalsteadMetrics([
            'n1' => 6,
            'n2' => 4,
            'N1' => 12,
            'N2' => 8,
        ]);

        $collection->add($metric1);
        $collection->add($metric2);

        $iterator = $collection->getIterator();

        $this->assertInstanceOf(\ArrayIterator::class, $iterator);
        $this->assertCount(2, $iterator);
        $this->assertSame($metric1, $iterator[0]);
        $this->assertSame($metric2, $iterator[1]);
    }

    public function testFilter(): void
    {
        $collection = new HalsteadMetricsCollection();
        $metric1 = new HalsteadMetrics([
            'n1' => 5,
            'n2' => 3,
            'N1' => 10,
            'N2' => 7,
        ]);
        $metric2 = new HalsteadMetrics([
            'n1' => 10,
            'n2' => 5,
            'N1' => 20,
            'N2' => 15,
        ]);

        $collection->add($metric1);
        $collection->add($metric2);

        $filteredCollection = $collection->filter(fn(HalsteadMetrics $m) => $m->getN1() > 5);

        $this->assertCount(1, $filteredCollection);
        $this->assertTrue($filteredCollection->contains($metric2));
        $this->assertFalse($filteredCollection->contains($metric1));
    }

    public function testContains(): void
    {
        $collection = new HalsteadMetricsCollection();
        $metric = new HalsteadMetrics([
            'n1' => 5,
            'n2' => 3,
            'N1' => 10,
            'N2' => 7,
        ]);

        $collection->add($metric);

        $this->assertTrue($collection->contains($metric));
    }

    public function testJsonSerialize(): void
    {
        $collection = new HalsteadMetricsCollection();
        $metric1 = new HalsteadMetrics([
            'n1' => 5,
            'n2' => 3,
            'N1' => 10,
            'N2' => 7,
        ]);
        $metric2 = new HalsteadMetrics([
            'n1' => 6,
            'n2' => 4,
            'N1' => 12,
            'N2' => 8,
        ]);

        $collection->add($metric1);
        $collection->add($metric2);

        $expectedJson = json_encode([$metric1, $metric2]);

        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($collection));
    }
}
