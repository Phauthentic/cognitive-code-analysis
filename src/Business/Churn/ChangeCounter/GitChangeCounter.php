<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChangeCounter;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

class GitChangeCounter implements ChangeCounterInterface
{
    /**
     * @throws CognitiveAnalysisException
     */
    public function getNumberOfChangesForFile(string $filename, string $since): int
    {
        $command = [
            'git', '-C', dirname($filename),
            'rev-list', '--since=' . $since, '--no-merges', '--count', 'HEAD', '--', basename($filename),
        ];

        // The array form runs the process without a shell. That is portable and keeps stderr separate.
        $pipes = [];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new CognitiveAnalysisException('Failed to execute git command for file: ' . $filename);
        }

        /** @var array<int, resource> $pipes */
        $stdout = trim((string)stream_get_contents($pipes[1]));
        $stderr = trim((string)stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $resultCode = proc_close($process);

        $output = $stdout === '' ? [] : explode("\n", $stdout);

        $this->assertValidExitCode($resultCode, $output, $filename, $stderr);

        return (int)$output[0];
    }

    /**
     * @param int $resultCode
     * @param array<int, string> $output
     * @param string $filename
     * @param string $errorOutput Optional stderr text of the process.
     * @return void
     * @throws CognitiveAnalysisException
     */
    public function assertValidExitCode(
        int $resultCode,
        array $output,
        string $filename,
        string $errorOutput = ''
    ): void {
        if ($resultCode !== 0 || empty($output)) {
            throw new CognitiveAnalysisException(
                'Failed to execute git command or no changes found for file: ' . $filename
                . ($errorOutput !== '' ? ' (' . $errorOutput . ')' : '')
            );
        }
    }
}
