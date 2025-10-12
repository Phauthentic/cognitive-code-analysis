<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;

class JsonReport extends AbstractReport
{
    /**
     * @param array<string, array<string, mixed>> $classes
     * @throws \JsonException|\Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function export(array $classes, string $filename): void
    {
        $this->assertFileIsWritable($filename);

        $data = [
            'createdAt' => (new DateTime())->format('Y-m-d H:i:s'),
            'classes' => $classes,
        ];

        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        $this->writeFile($filename, $jsonData);
    }
}
