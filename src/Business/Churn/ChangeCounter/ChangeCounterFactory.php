<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChangeCounter;

use InvalidArgumentException;

/**
 *
 */
class ChangeCounterFactory
{
    /**
     * @param string $type
     * @return ChangeCounterInterface
     */
    public function create(string $type): ChangeCounterInterface
    {
        return match ($type) {
            'git' => new GitChangeCounter(),
            default => throw new InvalidArgumentException("Unknown change counter type: $type"),
        };
    }
}
