<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter;

use JsonException;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;
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
        $data = [
            'createdAt' => (new DateTime())->format('Y-m-d H:i:s'),
            'classes' => $classes,
        ];

        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        if (file_put_contents($filename, $jsonData) === false) {
            throw new CognitiveAnalysisException("Unable to write to file: $filename");
        }
    }
}
