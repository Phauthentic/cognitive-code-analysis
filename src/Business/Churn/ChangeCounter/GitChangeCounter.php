<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChangeCounter;

class GitChangeCounter implements ChangeCounterInterface
{
    public function getNumberOfChangesForFile(string $filename): int
    {
        $command = sprintf(
            'git -C %s rev-list --since=%s --no-merges --count HEAD -- %s',
            escapeshellarg(dirname($filename)),
            escapeshellarg('1900-01-01'),
            escapeshellarg($filename)
        );

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        return (int)$output[0];
    }
}
