<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report;

use DOMDocument;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

/**
 * Checkstyle XML report for CI (Jenkins, Maven Checkstyle Plugin, IDEs).
 * Emits one violation per method that exceeds the cognitive complexity threshold.
 */
class CheckstyleReport implements ReportGeneratorInterface
{
    private const SOURCE = 'CognitiveComplexity';
    private const VERSION = '8.0';

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

        $violations = $this->filterViolations($metrics);
        $groupedByFile = $this->groupByFile($violations);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElement('checkstyle');
        $root->setAttribute('version', self::VERSION);
        $dom->appendChild($root);

        foreach ($groupedByFile as $filePath => $fileMetrics) {
            $normalizedPath = $this->normalizePath($filePath);
            $fileEl = $dom->createElement('file');
            $fileEl->setAttribute('name', $normalizedPath);
            $root->appendChild($fileEl);

            foreach ($fileMetrics as $metric) {
                $errorEl = $dom->createElement('error');
                $errorEl->setAttribute('line', (string) $metric->getLine());
                $errorEl->setAttribute('column', '1');
                $errorEl->setAttribute('severity', $this->scoreToSeverity($metric->getScore()));
                $errorEl->setAttribute('message', $this->buildMessage($metric));
                $errorEl->setAttribute('source', self::SOURCE);
                $fileEl->appendChild($errorEl);
            }
        }

        $xml = $dom->saveXML();
        if ($xml === false) {
            throw new CognitiveAnalysisException('Could not generate Checkstyle XML');
        }

        if (file_put_contents($filename, $xml) === false) {
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
     * @param CognitiveMetrics[] $violations
     * @return array<string, CognitiveMetrics[]>
     */
    private function groupByFile(array $violations): array
    {
        $grouped = [];
        foreach ($violations as $metric) {
            $path = $metric->getFileName();
            if (!isset($grouped[$path])) {
                $grouped[$path] = [];
            }
            $grouped[$path][] = $metric;
        }

        return $grouped;
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);

        return ltrim($path, './');
    }

    private function scoreToSeverity(float $score): string
    {
        $threshold = $this->config->scoreThreshold;
        if ($score >= $threshold * 2) {
            return 'error';
        }

        return 'warning';
    }

    private function buildMessage(CognitiveMetrics $metric): string
    {
        $threshold = $this->config->scoreThreshold;
        $score = $metric->getScore();
        $method = $metric->getMethod();

        return sprintf(
            'Method %s has cognitive complexity %s (threshold: %s)',
            $method,
            number_format($score, 1),
            number_format($threshold, 1)
        );
    }
}
