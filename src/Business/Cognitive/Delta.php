<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

final class Delta
{
    private bool $hasIncreased;
    private float $difference;

    public function __construct(float $before, float $after)
    {
        if ($before < $after) {
            $this->hasIncreased = true;
            $this->difference = $after - $before;
            return;
        }

        $this->hasIncreased = false;
        $this->difference = $after - $before;
    }

    public function getValue(): float
    {
        return $this->difference;
    }

    public function hasIncreased(): bool
    {
        return $this->hasIncreased;
    }

    public function __toString(): string
    {
        return (string)$this->difference;
    }

    public function hasNotChanged(): bool
    {
        return $this->difference === 0.0;
    }
}
