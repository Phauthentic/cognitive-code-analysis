<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Config;

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

        $halstead = new HalsteadConfig([
            'threshold' => $config['halstead']['threshold'],
        ]);

        return new Config($cognitive, $halstead);
    }
}
