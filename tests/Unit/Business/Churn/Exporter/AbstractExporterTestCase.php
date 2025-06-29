<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn\Exporter;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class AbstractExporterTestCase extends TestCase
{
    protected string $filename;

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    public function getTestData(): array
    {
        return [
            'Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics' => [
                'timesChanged' => 6,
                'score' => 2.042,
                'file' => '/home/florian/projects/cognitive-code-checker/src/Business/Cognitive/CognitiveMetrics.php',
                'churn' => 12.252,
            ],
            'Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRenderer' => [
                'timesChanged' => 10,
                'score' => 0.806,
                'file' => '/home/florian/projects/cognitive-code-checker/src/Command/Presentation/CognitiveMetricTextRenderer.php',
                'churn' => 8.06,
            ],
            'Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade' => [
                'timesChanged' => 8,
                'score' => 0.693,
                'file' => '/home/florian/projects/cognitive-code-checker/src/Business/MetricsFacade.php',
                'churn' => 5.544,
            ],
        ];
    }
}
