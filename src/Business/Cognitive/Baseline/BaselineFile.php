<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Baseline;

use JsonSerializable;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;

/**
 * Represents a baseline file with metadata including config hash and creation date.
 */
class BaselineFile implements JsonSerializable
{
    private const VERSION = '2.0';
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @param array<string, array<string, mixed>> $metrics
     */
    public function __construct(
        private readonly string $createdAt,
        private readonly string $configHash,
        /** @var array<string, array<string, mixed>> */
        private readonly array $metrics
    ) {
    }

    /**
     * Create a BaselineFile from a metrics collection and config.
     */
    public static function fromMetricsCollection(
        CognitiveMetricsCollection $metricsCollection,
        CognitiveConfig $config
    ): self {
        $createdAt = date(self::DATE_FORMAT);
        $configHash = self::generateConfigHash($config);
        $metrics = self::extractMetricsFromCollection($metricsCollection);

        return new self($createdAt, $configHash, $metrics);
    }

    /**
     * Create a BaselineFile from JSON data (supports both old and new formats).
     *
     * @param array<string, mixed> $data
     * @return array{baselineFile: self|null, metrics: array<string, mixed>}
     */
    public static function fromJson(array $data): array
    {
        // Check if this is the new format (has version field)
        if (isset($data['version']) && $data['version'] === self::VERSION) {
            $baselineFile = new self(
                $data['createdAt'],
                $data['configHash'],
                $data['metrics']
            );

            return [
                'baselineFile' => $baselineFile,
                'metrics' => $data['metrics']
            ];
        }

        // Old format - return null for baselineFile, data as metrics
        return [
            'baselineFile' => null,
            'metrics' => $data
        ];
    }

    /**
     * Generate a config hash for the given configuration.
     */
    public static function generateConfigHash(CognitiveConfig $config): string
    {
        $configArray = $config->toArray();
        $metricsConfig = $configArray['metrics'] ?? [];

        return md5(serialize($metricsConfig));
    }

    /**
     * Extract metrics data from a metrics collection in the expected format.
     *
     * @return array<string, array{methods: array<string, array<string, mixed>>}>
     */
    private static function extractMetricsFromCollection(CognitiveMetricsCollection $metricsCollection): array
    {
        /** @var array<string, array{methods: array<string, array<string, mixed>>}> $metrics */
        $metrics = [];
        $groupedByClass = $metricsCollection->groupBy('class');

        foreach ($groupedByClass as $class => $methods) {
            foreach ($methods as $methodMetrics) {
                $metrics[(string)$class]['methods'][$methodMetrics->getMethod()] = [
                    'class' => $methodMetrics->getClass(),
                    'method' => $methodMetrics->getMethod(),
                    'file' => $methodMetrics->getFileName(),
                    'line' => $methodMetrics->getLine(),
                    'lineCount' => $methodMetrics->getLineCount(),
                    'argCount' => $methodMetrics->getArgCount(),
                    'returnCount' => $methodMetrics->getReturnCount(),
                    'variableCount' => $methodMetrics->getVariableCount(),
                    'propertyCallCount' => $methodMetrics->getPropertyCallCount(),
                    'ifCount' => $methodMetrics->getIfCount(),
                    'ifNestingLevel' => $methodMetrics->getIfNestingLevel(),
                    'elseCount' => $methodMetrics->getElseCount(),
                    'lineCountWeight' => $methodMetrics->getLineCountWeight(),
                    'argCountWeight' => $methodMetrics->getArgCountWeight(),
                    'returnCountWeight' => $methodMetrics->getReturnCountWeight(),
                    'variableCountWeight' => $methodMetrics->getVariableCountWeight(),
                    'propertyCallCountWeight' => $methodMetrics->getPropertyCallCountWeight(),
                    'ifCountWeight' => $methodMetrics->getIfCountWeight(),
                    'ifNestingLevelWeight' => $methodMetrics->getIfNestingLevelWeight(),
                    'elseCountWeight' => $methodMetrics->getElseCountWeight(),
                    'score' => $methodMetrics->getScore(),
                ];
            }
        }

        return $metrics;
    }

    public function getVersion(): string
    {
        return self::VERSION;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getConfigHash(): string
    {
        return $this->configHash;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Validate if the config hash matches the current configuration.
     */
    public function validateConfigHash(CognitiveConfig $currentConfig): bool
    {
        $currentHash = self::generateConfigHash($currentConfig);
        return $this->configHash === $currentHash;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'version' => self::VERSION,
            'createdAt' => $this->createdAt,
            'configHash' => $this->configHash,
            'metrics' => $this->metrics,
        ];
    }
}
