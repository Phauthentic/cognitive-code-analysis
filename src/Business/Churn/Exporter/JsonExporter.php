<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter;

use JsonException;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 *
 */
class JsonExporter implements DataExporterInterface
{
    /**
     * @param array<string, array<string, mixed>> $classes
     * @throws JsonException|CognitiveAnalysisException
     */
    public function export(array $classes, string $filename): void
    {
        $jsonData = json_encode($classes, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        if (file_put_contents($filename, $jsonData) === false) {
            throw new CognitiveAnalysisException("Unable to write to file: $filename");
        }
    }
}
