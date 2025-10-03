<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChangeCounter;

interface ChangeCounterInterface
{
    public function getNumberOfChangesForFile(string $filename, string $since): int;
}
