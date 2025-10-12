<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter;

/**
 * Value object representing a single refactoring suggestion.
 */
final class RefactoringSuggestion
{
    public function __construct(
        public readonly string $metricName,
        public readonly string $technique,
        public readonly string $description,
        public readonly string $codeExample,
        public readonly int $priority,
        public readonly float $metricValue,
        public readonly float $threshold
    ) {
    }

    /**
     * Get the priority emoji for display.
     */
    public function getPriorityEmoji(): string
    {
        return match ($this->priority) {
            5 => '🔴',
            4 => '🟠',
            3 => '🟡',
            2 => '🟢',
            default => '⚪',
        };
    }

    /**
     * Get the priority label for display.
     */
    public function getPriorityLabel(): string
    {
        return match ($this->priority) {
            5 => 'Critical',
            4 => 'High',
            3 => 'Medium',
            2 => 'Low',
            default => 'Info',
        };
    }

    /**
     * Calculate how much the metric exceeds the threshold.
     */
    public function getExcessRatio(): float
    {
        if ($this->threshold <= 0) {
            return 0.0;
        }

        return ($this->metricValue - $this->threshold) / $this->threshold;
    }

    /**
     * Get a formatted reason for the suggestion.
     */
    public function getReason(): string
    {
        $excessRatio = $this->getExcessRatio();
        $percentage = round($excessRatio * 100, 1);

        return sprintf(
            '%s (%s) exceeds threshold (%s) by %s%%',
            ucfirst(str_replace('Count', ' Count', $this->metricName)),
            $this->metricValue,
            $this->threshold,
            $percentage
        );
    }
}
