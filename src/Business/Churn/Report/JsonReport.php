<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;

class JsonReport extends AbstractReport
{
    /**
     * @throws \JsonException|\Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function export(ChurnMetricsCollection $metrics, string $filename): void
    {
        $this->assertFileIsWritable($filename);

        $data = [
            'createdAt' => (new DateTime())->format('Y-m-d H:i:s'),
            'classes' => $metrics->toArray(),
        ];

        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        $this->writeFile($filename, $jsonData);
    }
}
