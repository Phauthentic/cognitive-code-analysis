<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

class CodeCoverageFactory
{
    /**
     * @throws CognitiveAnalysisException
     */
    public function createFromName(string $name, string $filePath): CoverageReportReaderInterface
    {
        return match (strtolower($name)) {
            'clover' => new CloverReader($filePath),
            'cobertura' => new CoberturaReader($filePath),
            default => throw new CognitiveAnalysisException("Unknown code coverage implementation: {$name}"),
        };
    }
}
