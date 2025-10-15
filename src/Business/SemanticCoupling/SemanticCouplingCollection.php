<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling;

/**
 * Collection of semantic coupling metrics with filtering and sorting capabilities.
 */
class SemanticCouplingCollection implements \Iterator, \Countable
{
    /**
     * @var SemanticCouplingMetrics[]
     */
    private array $metrics = [];

    public function add(SemanticCouplingMetrics $metric): void
    {
        $this->metrics[] = $metric;
    }

    public function addMultiple(array $metrics): void
    {
        foreach ($metrics as $metric) {
            if ($metric instanceof SemanticCouplingMetrics) {
                $this->add($metric);
            }
        }
    }

    public function filterByThreshold(float $threshold): self
    {
        $filtered = new self();
        foreach ($this->metrics as $metric) {
            if ($metric->getScore() >= $threshold) {
                $filtered->add($metric);
            }
        }
        return $filtered;
    }

    public function filterByGranularity(string $granularity): self
    {
        $filtered = new self();
        foreach ($this->metrics as $metric) {
            if ($metric->getGranularity() === $granularity) {
                $filtered->add($metric);
            }
        }
        return $filtered;
    }

    public function sortByScore(bool $descending = true): self
    {
        $sorted = clone $this;
        usort($sorted->metrics, function (SemanticCouplingMetrics $a, SemanticCouplingMetrics $b) use ($descending) {
            $comparison = $a->getScore() <=> $b->getScore();
            return $descending ? -$comparison : $comparison;
        });
        return $sorted;
    }

    public function getTop(int $limit): self
    {
        $sorted = $this->sortByScore(true);
        $top = new self();
        $count = 0;
        foreach ($sorted->metrics as $metric) {
            if ($count >= $limit) {
                break;
            }
            $top->add($metric);
            $count++;
        }
        return $top;
    }

    public function getAverageScore(): float
    {
        if (empty($this->metrics)) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($this->metrics as $metric) {
            $total += $metric->getScore();
        }

        return $total / count($this->metrics);
    }

    public function getMaxScore(): float
    {
        if (empty($this->metrics)) {
            return 0.0;
        }

        $max = 0.0;
        foreach ($this->metrics as $metric) {
            $max = max($max, $metric->getScore());
        }

        return $max;
    }

    public function getMinScore(): float
    {
        if (empty($this->metrics)) {
            return 0.0;
        }

        $min = 1.0;
        foreach ($this->metrics as $metric) {
            $min = min($min, $metric->getScore());
        }

        return $min;
    }

    public function getMedianScore(): float
    {
        if (empty($this->metrics)) {
            return 0.0;
        }

        $scores = array_map(fn(SemanticCouplingMetrics $m) => $m->getScore(), $this->metrics);
        sort($scores);
        $count = count($scores);
        
        if ($count % 2 === 0) {
            return ($scores[$count / 2 - 1] + $scores[$count / 2]) / 2;
        }
        
        return $scores[intval($count / 2)];
    }

    public function toArray(): array
    {
        return array_map(fn(SemanticCouplingMetrics $m) => $m->toArray(), $this->metrics);
    }

    public function current(): SemanticCouplingMetrics
    {
        return current($this->metrics);
    }

    public function next(): void
    {
        next($this->metrics);
    }

    public function key(): int
    {
        return key($this->metrics);
    }

    public function valid(): bool
    {
        return key($this->metrics) !== null;
    }

    public function rewind(): void
    {
        reset($this->metrics);
    }

    public function count(): int
    {
        return count($this->metrics);
    }
}
