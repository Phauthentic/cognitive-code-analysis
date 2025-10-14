<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\Datetime;
use PHPUnit\Framework\TestCase;

class AbstractReporterTestCase extends TestCase
{
    protected string $filename;

    protected function setUp(): void
    {
        parent::setUp();
        Datetime::$fixedDate = '2023-10-01 12:00:00';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
        Datetime::$fixedDate = null;
    }

    protected function getTestData(): ChurnMetricsCollection
    {
        $collection = new ChurnMetricsCollection();

        $collection->add(new ChurnMetrics(
            className: 'Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics',
            file: '/home/florian/projects/cognitive-code-checker/src/Business/Cognitive/CognitiveMetrics.php',
            score: 2.042,
            timesChanged: 6,
            churn: 12.252
        ));

        $collection->add(new ChurnMetrics(
            className: 'Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRenderer',
            file: '/home/florian/projects/cognitive-code-checker/src/Command/Presentation/CognitiveMetricTextRenderer.php',
            score: 0.806,
            timesChanged: 10,
            churn: 8.06
        ));

        $collection->add(new ChurnMetrics(
            className: 'Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade',
            file: '/home/florian/projects/cognitive-code-checker/src/Business/MetricsFacade.php',
            score: 0.693,
            timesChanged: 8,
            churn: 5.544
        ));

        return $collection;
    }

    protected function getTestDataWithCoverage(): ChurnMetricsCollection
    {
        $collection = new ChurnMetricsCollection();

        $collection->add(new ChurnMetrics(
            className: 'Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics',
            file: '/home/florian/projects/cognitive-code-checker/src/Business/Cognitive/CognitiveMetrics.php',
            score: 2.042,
            timesChanged: 6,
            churn: 12.252,
            coverage: 0.85,
            riskChurn: 1.8378,
            riskLevel: 'low'
        ));

        $collection->add(new ChurnMetrics(
            className: 'Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRenderer',
            file: '/home/florian/projects/cognitive-code-checker/src/Command/Presentation/CognitiveMetricTextRenderer.php',
            score: 0.806,
            timesChanged: 10,
            churn: 8.06,
            coverage: 0.65,
            riskChurn: 2.821,
            riskLevel: 'medium'
        ));

        $collection->add(new ChurnMetrics(
            className: 'Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade',
            file: '/home/florian/projects/cognitive-code-checker/src/Business/MetricsFacade.php',
            score: 0.693,
            timesChanged: 8,
            churn: 5.544,
            coverage: 0.92,
            riskChurn: 0.443,
            riskLevel: 'low'
        ));

        return $collection;
    }
}
