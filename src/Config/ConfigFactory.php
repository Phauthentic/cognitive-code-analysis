<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

/**
 * @phpstan-type MetricConfigArray array{threshold: int|float, scale: float, enabled: bool}
 * @phpstan-type CognitiveSectionArray array{
 *     excludeFilePatterns: list<string>,
 *     excludePatterns: list<string>,
 *     metrics: array<string, MetricConfigArray>,
 *     showOnlyMethodsExceedingThreshold: bool,
 *     scoreThreshold: float,
 *     showHalsteadComplexity?: bool,
     *     showCyclomaticComplexity?: bool,
     *     showUnderstandability?: bool,
     *     groupByClass?: bool,
 *     showDetailedCognitiveMetrics?: bool,
 *     cache?: array{enabled?: bool, directory?: string},
 *     performance?: array{batchSize?: int},
 *     customReporters?: array<string, array<string, mixed>>
 * }
 */
class ConfigFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public function fromArray(array $config): CognitiveConfig
    {
        $cognitive = $this->resolveCognitiveSection($config);

        $metrics = array_map(
            fn (array $metric): MetricsConfig => new MetricsConfig(
                (int) $metric['threshold'],
                $metric['scale'],
                $metric['enabled'],
            ),
            $cognitive['metrics'],
        );

        $cacheConfig = null;
        if (isset($cognitive['cache'])) {
            $cacheConfig = new CacheConfig(
                enabled: $cognitive['cache']['enabled'] ?? true,
                directory: $cognitive['cache']['directory'] ?? './.phpcca.cache',
            );
        }

        $performanceConfig = null;
        if (isset($cognitive['performance'])) {
            $performanceConfig = new PerformanceConfig(
                batchSize: $cognitive['performance']['batchSize'] ?? 100
            );
        }

        return new CognitiveConfig(
            excludeFilePatterns: $cognitive['excludeFilePatterns'],
            excludePatterns: $cognitive['excludePatterns'],
            metrics: $metrics,
            showOnlyMethodsExceedingThreshold: $cognitive['showOnlyMethodsExceedingThreshold'],
            scoreThreshold: $cognitive['scoreThreshold'],
            showHalsteadComplexity: $cognitive['showHalsteadComplexity'] ?? false,
            showCyclomaticComplexity: $cognitive['showCyclomaticComplexity'] ?? false,
            showUnderstandability: $cognitive['showUnderstandability'] ?? false,
            groupByClass: $cognitive['groupByClass'] ?? true,
            showDetailedCognitiveMetrics: $cognitive['showDetailedCognitiveMetrics'] ?? true,
            cache: $cacheConfig,
            performance: $performanceConfig,
            customReporters: $cognitive['customReporters'] ?? []
        );
    }

    /**
     * @param array<string, mixed> $config
     * @return CognitiveSectionArray
     */
    private function resolveCognitiveSection(array $config): array
    {
        $cognitive = $config['cognitive'] ?? null;
        if (!is_array($cognitive)) {
            throw new ConfigException('Configuration must contain a "cognitive" section.');
        }

        /** @var CognitiveSectionArray $cognitive */
        return $cognitive;
    }
}
