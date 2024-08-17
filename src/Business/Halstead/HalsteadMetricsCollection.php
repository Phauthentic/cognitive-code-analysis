<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business\Halstead;

use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * HalsteadMetricsCollection class
 *
 * @implements IteratorAggregate<int, HalsteadMetrics>
 */
class HalsteadMetricsCollection implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @var HalsteadMetrics[]
     */
    private array $metrics = [];

    /**
     * Add a HalsteadMetrics object to the collection
     */
    public function add(HalsteadMetrics $metric): void
    {
        $this->metrics[] = $metric;
    }

    /**
     * Filter the collection using a callback function
     *
     * @return self A new collection with filtered results
     */
    public function filter(Closure $callback): self
    {
        $filtered = array_filter($this->metrics, $callback);

        $newCollection = new self();
        foreach ($filtered as $metric) {
            $newCollection->add($metric);
        }

        return $newCollection;
    }

    /**
     * Get an iterator for the collection
     *
     * @return ArrayIterator<int, HalsteadMetrics>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->metrics);
    }

    /**
     * Get the count of metrics in the collection
     */
    public function count(): int
    {
        return count($this->metrics);
    }

    /**
     * Check if a specific HalsteadMetrics object is in the collection
     */
    public function contains(HalsteadMetrics $otherMetric): bool
    {
        foreach ($this->metrics as $metric) {
            if ($otherMetric->equals($metric)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->metrics;
    }
}
