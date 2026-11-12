<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetricsCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers the collection helpers that custom churn reporters can use.
 */
class ChurnMetricsCollectionTest extends TestCase
{
    private function createCollection(): ChurnMetricsCollection
    {
        $collection = new ChurnMetricsCollection();
        $collection->add(new ChurnMetrics('Low', 'Low.php', 1.0, 2, 2.0));
        $collection->add(new ChurnMetrics('High', 'High.php', 3.0, 10, 30.0, 0.5, 15.0, 'HIGH'));
        $collection->add(new ChurnMetrics('Mid', 'Mid.php', 5.0, 2, 10.0, 0.8));

        return $collection;
    }

    /**
     * @return array<int, string>
     */
    private function classNames(ChurnMetricsCollection $collection): array
    {
        $names = [];
        foreach ($collection as $metric) {
            $names[] = $metric->getClassName();
        }

        return $names;
    }

    public function testFilterWithChurnGreaterThan(): void
    {
        $collection = $this->createCollection();

        $this->assertSame(['High', 'Mid'], $this->classNames($collection->filterWithChurnGreaterThan(2.0)));
        $this->assertSame([], $this->classNames($collection->filterWithChurnGreaterThan(30.0)));
    }

    public function testFilterWithScoreGreaterThan(): void
    {
        $collection = $this->createCollection();

        $this->assertSame(['High', 'Mid'], $this->classNames($collection->filterWithScoreGreaterThan(1.0)));
        $this->assertSame(['Mid'], $this->classNames($collection->filterWithScoreGreaterThan(3.0)));
    }

    public function testFilterWithCoverage(): void
    {
        $this->assertSame(['High', 'Mid'], $this->classNames($this->createCollection()->filterWithCoverage()));
    }

    public function testFilterWithRiskData(): void
    {
        $this->assertSame(['High'], $this->classNames($this->createCollection()->filterWithRiskData()));
    }

    public function testSortByScoreDescending(): void
    {
        $collection = $this->createCollection();

        $this->assertSame(['Mid', 'High', 'Low'], $this->classNames($collection->sortByScoreDescending()));
        $this->assertSame(['Low', 'High', 'Mid'], $this->classNames($collection), 'The original collection is not changed');
    }

    public function testSortByChurnDescending(): void
    {
        $this->assertSame(['High', 'Mid', 'Low'], $this->classNames($this->createCollection()->sortByChurnDescending()));
    }

    public function testGetClassNames(): void
    {
        $this->assertSame(['Low', 'High', 'Mid'], $this->createCollection()->getClassNames());
        $this->assertSame([], (new ChurnMetricsCollection())->getClassNames());
    }

    public function testIsEmpty(): void
    {
        $this->assertFalse($this->createCollection()->isEmpty());
        $this->assertTrue((new ChurnMetricsCollection())->isEmpty());
        $this->assertTrue($this->createCollection()->filterWithChurnGreaterThan(100.0)->isEmpty());
    }
}
