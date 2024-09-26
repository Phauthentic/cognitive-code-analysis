<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business\Cognitive;

use ArrayIterator;
use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;

/**
 * CognitiveMetricsCollection class
 *
 * @implements IteratorAggregate<int, CognitiveMetrics>
 */
class CognitiveMetricsCollection implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @var CognitiveMetrics[]
     */
    private array $metrics = [];

    /**
     * Add a CognitiveMetrics object to the collection
     */
    public function add(CognitiveMetrics $metric): void
    {
        $this->metrics[$metric->getClass() . '::' . $metric->getMethod()] = $metric;
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
     * @return ArrayIterator<int, CognitiveMetrics>
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

    public function filterWithScoreGreaterThan(float $score): CognitiveMetricsCollection
    {
        return $this->filter(function (CognitiveMetrics $metric) use ($score) {
            return $metric->getScore() > $score;
        });
    }

    public function contains(CognitiveMetrics $otherMetric): bool
    {
        return isset($this->metrics[$otherMetric->getClass() . '::' .  $otherMetric->getMethod()]);
    }

    public function getClassWithMethod(string $class, string $method): ?CognitiveMetrics
    {
        if (isset($this->metrics[$class . '::' . $method])) {
            return $this->metrics[$class . '::' . $method];
        }

        return null;
    }

    public function filterByClassName(string $className): CognitiveMetricsCollection
    {
        return $this->filter(function (CognitiveMetrics $metric) use ($className) {
            return $metric->getClass() === $className;
        });
    }

    /**
     * Group the collection by a property of the CognitiveMetrics object
     *
     * @param string $property The property to group by
     * @return array<int|string, CognitiveMetricsCollection> An associative array where keys are property values and values are collections
     * @throws InvalidArgumentException if the property does not exist
     */
    public function groupBy(string $property): array
    {
        $grouped = [];

        foreach ($this->metrics as $metric) {
            $getter = 'get' . ucfirst($property);
            if (!method_exists($metric, $getter)) {
                throw new InvalidArgumentException("Property '$property' does not exist in CognitiveMetrics class");
            }

            $key = $metric->$getter();

            if (!isset($grouped[$key])) {
                $grouped[$key] = new self();
            }

            $grouped[$key]->add($metric);
        }

        return $grouped;
    }

    /**
     * @return array<int, CognitiveMetrics>
     */
    public function jsonSerialize(): array
    {
        return $this->metrics;
    }
}
