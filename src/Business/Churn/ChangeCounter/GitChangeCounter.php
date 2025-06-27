<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChangeCounter;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 *
 */
class GitChangeCounter implements ChangeCounterInterface
{
    /**
     * @throws CognitiveAnalysisException
     */
    public function getNumberOfChangesForFile(string $filename, string $since): int
    {
        $command = sprintf(
            'git -C %s rev-list --since=%s --no-merges --count HEAD -- %s',
            escapeshellarg(dirname($filename)),
            escapeshellarg($since),
            escapeshellarg(basename($filename))
        );

        $output = [];
        $resultCode = 0;
        exec($command, $output, $resultCode);

        $this->assertValidExitCode($resultCode, $output, $filename);

        return (int)$output[0];
    }

    /**
     * @param int $resultCode
     * @param array<int, string> $output
     * @param string $filename
     * @return void
     * @throws CognitiveAnalysisException
     */
    public function assertValidExitCode(int $resultCode, array $output, string $filename): void
    {
        if ($resultCode !== 0 || empty($output)) {
            throw new CognitiveAnalysisException(
                'Failed to execute git command or no changes found for file: ' . $filename
            );
        }
    }
}
