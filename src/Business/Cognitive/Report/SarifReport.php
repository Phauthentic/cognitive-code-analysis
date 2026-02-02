<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

/**
 * SARIF 2.1.0 report for GitHub Code Scanning.
 * One result per method that exceeds the cognitive complexity threshold.
 */
class SarifReport implements ReportGeneratorInterface
{
    private const SCHEMA = 'https://json.schemastore.org/sarif-2.1.0.json';
    private const VERSION = '2.1.0';
    private const RULE_ID = 'cognitive-complexity';
    private const TOOL_NAME = 'cognitive-code-checker';

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
        $results = [];
        foreach ($violations as $metric) {
            $results[] = $this->buildResult($metric);
        }

        $payload = [
            '$schema' => self::SCHEMA,
            'version' => self::VERSION,
            'runs' => [
                [
                    'tool' => [
                        'driver' => [
                            'name' => self::TOOL_NAME,
                            'semanticVersion' => '1.0.0',
                            'rules' => [
                                [
                                    'id' => self::RULE_ID,
                                    'name' => 'Cognitive Complexity',
                                    'shortDescription' => [
                                        'text' => 'Method exceeds cognitive complexity threshold',
                                    ],
                                    'defaultConfiguration' => [
                                        'level' => 'warning',
                                    ],
                                    'properties' => [
                                        'precision' => 'high',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'results' => $results,
                ],
            ],
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

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
    private function buildResult(CognitiveMetrics $metric): array
    {
        $path = $this->normalizePath($metric->getFileName());
        $line = $metric->getLine();
        $message = sprintf(
            'Method %s has cognitive complexity %s (threshold: %s)',
            $metric->getMethod(),
            number_format($metric->getScore(), 1),
            number_format($this->config->scoreThreshold, 1)
        );
        $level = $this->scoreToLevel($metric->getScore());
        $fingerprint = $this->computeFingerprint($metric);

        return [
            'ruleId' => self::RULE_ID,
            'level' => $level,
            'message' => [
                'text' => $message,
            ],
            'locations' => [
                [
                    'physicalLocation' => [
                        'artifactLocation' => [
                            'uri' => $path,
                        ],
                        'region' => [
                            'startLine' => $line,
                        ],
                    ],
                ],
            ],
            'partialFingerprints' => [
                'primaryLocationLineHash' => $fingerprint,
            ],
        ];
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
        if ($score > $threshold) {
            return 'warning';
        }

        return 'note';
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
