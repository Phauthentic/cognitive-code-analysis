<?php

declare(strict_types=1);

namespace Phauthentic\CodeQuality\Business;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Closure;

/**
 * MetricsCollection class
 *
 * @implements IteratorAggregate<int, Metrics>
 */
class MetricsCollection implements IteratorAggregate, Countable
{
    /**
     * @var Metrics[]
     */
    private array $metrics = [];

    /**
     * Add a Metrics object to the collection
     */
    public function add(Metrics $metric): void
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
     * @return ArrayIterator<int, Metrics>
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

    public function filterWithScoreGreaterThan(float $score): MetricsCollection
    {
        return $this->filter(function (Metrics $metric) use ($score) {
            return $metric->getScore() > $score;
        });
    }

    public function contains(Metrics $otherMetric): bool
    {
        foreach ($this->metrics as $metric) {
            if ($otherMetric->equals($metric)) {
                return true;
            }
        }

        return false;
    }

    public function filterByClassName(string $className): MetricsCollection
    {
        return $this->filter(function (Metrics $metric) use ($className) {
            return $metric->getClass() === $className;
        });
    }

    /**
     * Group the collection by a property of the Metrics object
     *
     * @param string $property The property to group by
     * @return array<string, MetricsCollection> An associative array where keys are property values and values are collections
     * @throws InvalidArgumentException if the property does not exist
     */
    public function groupBy(string $property): array
    {
        $grouped = [];

        foreach ($this->metrics as $metric) {
            // Dynamically access the property
            $getter = 'get' . ucfirst($property);
            if (!method_exists($metric, $getter)) {
                throw new InvalidArgumentException("Property '$property' does not exist in Metrics class");
            }

            $key = $metric->$getter();

            // Initialize a new collection if this is the first metric for this key
            if (!isset($grouped[$key])) {
                $grouped[$key] = new self();
            }

            $grouped[$key]->add($metric);
        }

        return $grouped;
    }
}
