<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

/**
 * GitLab Code Quality (CodeClimate-style) JSON report.
 * One issue per method that exceeds the cognitive complexity threshold.
 */
class GitLabCodeQualityReport implements ReportGeneratorInterface
{
    private const CHECK_NAME = 'cognitive-complexity';

    public function __construct(
        private readonly CognitiveConfig $config
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function export(CognitiveMetricsCollection $metrics, string $filename): void
    {
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            throw new CognitiveAnalysisException(sprintf('Directory %s does not exist', $directory));
        }

        $violations = $this->filterViolations($metrics);
        $issues = [];
        foreach ($violations as $metric) {
            $issues[] = $this->buildIssue($metric);
        }

        $json = json_encode($issues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (file_put_contents($filename, $json) === false) {
            throw new CognitiveAnalysisException("Unable to write to file: {$filename}");
        }
    }

    /**
     * @return CognitiveMetrics[]
     */
    private function filterViolations(CognitiveMetricsCollection $metrics): array
    {
        $result = [];
        foreach ($metrics as $metric) {
            if ($metric->getScore() <= $this->config->scoreThreshold) {
                continue;
            }

            $result[] = $metric;
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildIssue(CognitiveMetrics $metric): array
    {
        $threshold = $this->config->scoreThreshold;
        $score = $metric->getScore();
        $description = sprintf(
            'Method %s has cognitive complexity %s (threshold: %s)',
            $metric->getMethod(),
            number_format($score, 1),
            number_format($threshold, 1)
        );
        $path = $this->normalizePath($metric->getFileName());

        return [
            'description' => $description,
            'check_name' => self::CHECK_NAME,
            'fingerprint' => $this->computeFingerprint($metric),
            'severity' => $this->scoreToSeverity($score),
            'location' => [
                'path' => $path,
                'lines' => [
                    'begin' => $metric->getLine(),
                ],
            ],
        ];
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return ltrim($path, './');
    }

    private function scoreToSeverity(float $score): string
    {
        $threshold = $this->config->scoreThreshold;
        $ratio = $threshold > 0 ? $score / $threshold : 0;

        if ($ratio >= 3) {
            return 'blocker';
        }
        if ($ratio >= 2) {
            return 'critical';
        }
        if ($ratio >= 1.5) {
            return 'major';
        }
        if ($ratio > 1) {
            return 'minor';
        }

        return 'info';
    }

    private function computeFingerprint(CognitiveMetrics $metric): string
    {
        $content = sprintf(
            '%s:%d:%s::%s',
            $metric->getFileName(),
            $metric->getLine(),
            $metric->getClass(),
            $metric->getMethod()
        );

        return hash('sha256', $content);
    }
}
