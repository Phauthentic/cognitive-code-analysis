<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

/**
 * GitHub Actions workflow command report (::warning / ::error).
 * Writes one line per method over threshold for CI log annotations.
 */
class GitHubActionsReport implements ReportGeneratorInterface
{
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

        $lines = [];
        foreach ($metrics as $metric) {
            if ($metric->getScore() <= $this->config->scoreThreshold) {
                continue;
            }

            $level = $this->scoreToLevel($metric->getScore());
            $path = $this->normalizePath($metric->getFileName());
            $line = $metric->getLine();
            $message = $this->buildMessage($metric);
            $lines[] = sprintf('::%s file=%s,line=%d::%s', $level, $path, $line, $message);
        }

        $content = implode("\n", $lines);
        if ($lines !== []) {
            $content .= "\n";
        }

        if (file_put_contents($filename, $content) === false) {
            throw new CognitiveAnalysisException("Unable to write to file: {$filename}");
        }
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return ltrim($path, './');
    }

    private function scoreToLevel(float $score): string
    {
        $threshold = $this->config->scoreThreshold;
        if ($score >= $threshold * 2) {
            return 'error';
        }

        return 'warning';
    }

    private function buildMessage(CognitiveMetrics $metric): string
    {
        $score = $metric->getScore();
        $threshold = $this->config->scoreThreshold;
        $method = $metric->getMethod();

        return sprintf(
            'Method %s has cognitive complexity %s (threshold: %s)',
            $method,
            number_format($score, 1),
            number_format($threshold, 1)
        );
    }
}
