<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report;

use DOMDocument;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

/**
 * JUnit XML report for CI (Jenkins JUnit plugin, Maven Surefire).
 * One testcase per method; methods over threshold are reported as failures.
 */
class JUnitReport implements ReportGeneratorInterface
{
    private const SUITE_NAME = 'Cognitive Complexity';
    private const FAILURE_TYPE = 'CognitiveComplexity';

    public function __construct(
        private readonly CognitiveConfig $config
    ) {
    }

    public function export(CognitiveMetricsCollection $metrics, string $filename): void
    {
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            throw new CognitiveAnalysisException(sprintf('Directory %s does not exist', $directory));
        }

        $methods = iterator_to_array($metrics);
        $failureCount = $this->countFailures($methods);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $testsuites = $dom->createElement('testsuites');
        $testsuites->setAttribute('name', self::SUITE_NAME);
        $testsuites->setAttribute('tests', (string) count($methods));
        $testsuites->setAttribute('failures', (string) $failureCount);
        $testsuites->setAttribute('errors', '0');
        $dom->appendChild($testsuites);

        $testsuite = $dom->createElement('testsuite');
        $testsuite->setAttribute('name', self::SUITE_NAME);
        $testsuite->setAttribute('tests', (string) count($methods));
        $testsuite->setAttribute('failures', (string) $failureCount);
        $testsuite->setAttribute('errors', '0');
        $testsuites->appendChild($testsuite);

        foreach ($methods as $metric) {
            $testcase = $dom->createElement('testcase');
            $testcase->setAttribute('classname', $metric->getClass());
            $testcase->setAttribute('name', $metric->getMethod());
            $testcase->setAttribute('time', '0');
            $testsuite->appendChild($testcase);

            if ($metric->getScore() <= $this->config->scoreThreshold) {
                continue;
            }

            $failure = $dom->createElement('failure');
            $failure->setAttribute('message', $this->buildFailureMessage($metric));
            $failure->setAttribute('type', self::FAILURE_TYPE);
            $testcase->appendChild($failure);
        }

        $xml = $dom->saveXML();
        if ($xml === false) {
            throw new CognitiveAnalysisException('Could not generate JUnit XML');
        }

        if (file_put_contents($filename, $xml) === false) {
            throw new CognitiveAnalysisException("Unable to write to file: {$filename}");
        }
    }

    /**
     * @param CognitiveMetrics[] $methods
     */
    private function countFailures(array $methods): int
    {
        $count = 0;
        foreach ($methods as $metric) {
            if ($metric->getScore() <= $this->config->scoreThreshold) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    private function buildFailureMessage(CognitiveMetrics $metric): string
    {
        $score = $metric->getScore();
        $threshold = $this->config->scoreThreshold;

        return sprintf(
            'Cognitive complexity %s exceeds threshold %s',
            number_format($score, 1),
            number_format($threshold, 1)
        );
    }
}
