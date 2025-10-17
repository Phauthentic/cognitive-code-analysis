<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline;

use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsCommandContext;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Execution context that flows through pipeline stages.
 * Stores command state, timing information, and intermediate results.
 */
class ExecutionContext
{
    /** @var array<string, float> */
    private array $timings = [];
    /** @var array<string, mixed> */
    private array $statistics = [];
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(
        private readonly CognitiveMetricsCommandContext $commandContext,
        private readonly OutputInterface $output
    ) {
    }

    /**
     * Get the command context.
     */
    public function getCommandContext(): CognitiveMetricsCommandContext
    {
        return $this->commandContext;
    }

    /**
     * Get the output interface.
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * Record timing for a stage.
     */
    public function recordTiming(string $stageName, float $duration): void
    {
        $this->timings[$stageName] = $duration;
    }

    /**
     * Get timing for a specific stage.
     */
    public function getTiming(string $stageName): ?float
    {
        return $this->timings[$stageName] ?? null;
    }

    /**
     * Get all timings.
     *
     * @return array<string, float>
     */
    public function getTimings(): array
    {
        return $this->timings;
    }

    /**
     * Get total execution time.
     */
    public function getTotalTime(): float
    {
        return array_sum($this->timings);
    }

    /**
     * Set a data value in the context.
     */
    public function setData(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Get a data value from the context.
     */
    public function getData(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Check if a data key exists in the context.
     */
    public function hasData(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Increment a statistic counter.
     */
    public function incrementStatistic(string $key, int $amount = 1): void
    {
        $this->statistics[$key] = ($this->statistics[$key] ?? 0) + $amount;
    }

    /**
     * Set a statistic value.
     */
    public function setStatistic(string $key, mixed $value): void
    {
        $this->statistics[$key] = $value;
    }

    /**
     * Get a statistic value.
     */
    public function getStatistic(string $key): mixed
    {
        return $this->statistics[$key] ?? null;
    }

    /**
     * Get all statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }
}
