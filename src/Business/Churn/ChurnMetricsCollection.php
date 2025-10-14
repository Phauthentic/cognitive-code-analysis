<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn;

use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * ChurnMetricsCollection class for managing collections of ChurnMetrics.
 *
 * @implements IteratorAggregate<string, ChurnMetrics>
 * @SuppressWarnings("PHPMD.TooManyPublicMethods")
 */
class ChurnMetricsCollection implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @var ChurnMetrics[]
     */
    private array $metrics = [];

    /**
     * Add a ChurnMetrics object to the collection.
     */
    public function add(ChurnMetrics $metric): void
    {
        $this->metrics[$metric->getClassName()] = $metric;
    }

    /**
     * Filter the collection using a callback function.
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
     * Get an iterator for the collection.
     *
     * @return Traversable<string, ChurnMetrics>
     */
    #[\ReturnTypeWillChange]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->metrics);
    }

    /**
     * Get the count of metrics in the collection.
     */
    public function count(): int
    {
        return count($this->metrics);
    }

    /**
     * Check if the collection contains a metric for the given class name.
     */
    public function contains(string $className): bool
    {
        return isset($this->metrics[$className]);
    }

    /**
     * Get a metric by class name.
     */
    public function getByClassName(string $className): ?ChurnMetrics
    {
        return $this->metrics[$className] ?? null;
    }

    /**
     * Filter metrics with churn greater than the specified value.
     */
    public function filterWithChurnGreaterThan(float $churn): self
    {
        return $this->filter(function (ChurnMetrics $metric) use ($churn) {
            return $metric->getChurn() > $churn;
        });
    }

    /**
     * Filter metrics with score greater than the specified value.
     */
    public function filterWithScoreGreaterThan(float $score): self
    {
        return $this->filter(function (ChurnMetrics $metric) use ($score) {
            return $metric->getScore() > $score;
        });
    }

    /**
     * Filter metrics that have coverage data.
     */
    public function filterWithCoverage(): self
    {
        return $this->filter(function (ChurnMetrics $metric) {
            return $metric->hasCoverageData();
        });
    }

    /**
     * Filter metrics that have risk data.
     */
    public function filterWithRiskData(): self
    {
        return $this->filter(function (ChurnMetrics $metric) {
            return $metric->hasRiskData();
        });
    }

    /**
     * Sort the collection by churn in descending order.
     *
     * @SuppressWarnings("PHPMD.ShortVariable")
     */
    public function sortByChurnDescending(): self
    {
        $sorted = $this->metrics;
        uasort($sorted, function (ChurnMetrics $a, ChurnMetrics $b) {
            return $b->getChurn() <=> $a->getChurn();
        });

        $newCollection = new self();
        foreach ($sorted as $metric) {
            $newCollection->add($metric);
        }

        return $newCollection;
    }

    /**
     * Sort the collection by score in descending order.
     *
     * @SuppressWarnings("PHPMD.ShortVariable")
     */
    public function sortByScoreDescending(): self
    {
        $sorted = $this->metrics;
        uasort($sorted, function (ChurnMetrics $a, ChurnMetrics $b) {
            return $b->getScore() <=> $a->getScore();
        });

        $newCollection = new self();
        foreach ($sorted as $metric) {
            $newCollection->add($metric);
        }

        return $newCollection;
    }

    /**
     * Convert to array format (for backward compatibility).
     *
     * @return array<string, array<string, mixed>>
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->metrics as $className => $metric) {
            $result[$className] = $metric->toArray();
        }
        return $result;
    }

    /**
     * Create collection from array format (for backward compatibility).
     *
     * @SuppressWarnings("PHPMD.StaticAccess")
     * @param array<string, array<string, mixed>> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $collection = new self();
        foreach ($data as $className => $metricData) {
            $collection->add(ChurnMetrics::fromArray($className, $metricData));
        }
        return $collection;
    }

    /**
     * @return array<int, ChurnMetrics>
     */
    public function jsonSerialize(): array
    {
        return array_values($this->metrics);
    }

    /**
     * Get all class names in the collection.
     *
     * @return array<string>
     */
    public function getClassNames(): array
    {
        return array_keys($this->metrics);
    }

    /**
     * Check if the collection is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->metrics);
    }

    /**
     * Clear all metrics from the collection.
     */
    public function clear(): void
    {
        $this->metrics = [];
    }
}
