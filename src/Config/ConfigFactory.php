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
     * @return Config
     */
    public function fromArray(array $config): Config
    {
        $metrics = [];
        foreach ($config['cognitive']['metrics'] as $name => $metric) {
            $metrics[$name] = new MetricsConfig(
                $metric['threshold'],
                $metric['scale'],
                $metric['enabled']
            );
        }

        $cognitive = new CognitiveConfig(
            $config['cognitive']['excludeFilePatterns'],
            $config['cognitive']['excludePatterns'],
            $metrics
        );

        return new Config($cognitive);
    }
}
