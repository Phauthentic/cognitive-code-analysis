<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

/**
 *
 */
class ConfigFactory
{
    /**
     * @param array<string, mixed> $config
     * @return CognitiveConfig
     */
    public function fromArray(array $config): CognitiveConfig
    {
        $metrics = [];
        foreach ($config['cognitive']['metrics'] as $name => $metric) {
            $metrics[$name] = new MetricsConfig(
                $metric['threshold'],
                $metric['scale'],
                $metric['enabled']
            );
        }

        return new CognitiveConfig(
            $config['cognitive']['excludeFilePatterns'],
            $config['cognitive']['excludePatterns'],
            $metrics,
            $config['cognitive']['showOnlyMethodsExceedingThreshold'],
            $config['cognitive']['scoreThreshold']
        );
    }
}
