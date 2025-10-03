<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

abstract class AbstractExporter implements DataExporterInterface
{
    /**
     * @var array<string>
     */
    private array $header = [
        'Class',
        'File',
        'Score',
        'Churn',
        'Times Changed',
    ];

    /**
     * @throws CognitiveAnalysisException
     */
    protected function writeFile(string $filename, string $content): void
    {
        if (file_put_contents($filename, $content) === false) {
            throw new CognitiveAnalysisException("Unable to write to file: $filename");
        }
    }

    /**
     * @throws CognitiveAnalysisException
     */
    protected function assertFileIsWritable(string $filename): void
    {
        if (file_exists($filename) && !is_writable($filename)) {
            throw new CognitiveAnalysisException(sprintf('File %s is not writable', $filename));
        }

        $dir = dirname($filename);
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new CognitiveAnalysisException(sprintf('Directory %s does not exist for file %s', $dir, $filename));
        }
    }
}
