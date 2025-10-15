<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;

/**
 * Abstract base class for semantic coupling reports.
 */
abstract class AbstractReport implements ReportGeneratorInterface
{
    /**
     * Assert that the file is writable.
     *
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    protected function assertFileIsWritable(string $filename): void
    {
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            throw new \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException(
                "Directory does not exist: {$directory}"
            );
        }

        if (!is_writable($directory)) {
            throw new \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException(
                "Directory is not writable: {$directory}"
            );
        }
    }

    /**
     * Write content to file.
     */
    protected function writeFile(string $filename, string $content): void
    {
        $result = file_put_contents($filename, $content);
        if ($result === false) {
            throw new \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException(
                "Failed to write file: {$filename}"
            );
        }
    }

    /**
     * Get current timestamp.
     */
    protected function getCurrentTimestamp(): string
    {
        return (new DateTime())->format('Y-m-d H:i:s');
    }
}
