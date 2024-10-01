<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

use SplFileInfo;

/**
 * CognitiveMetricsCollector class that collects cognitive metrics from source files
 */
interface FindMetricsPluginInterface
{
    public function beforeFindMetrics(SplFileInfo $fileInfo): void;

    public function afterFindMetrics(SplFileInfo $fileInfo): void;

    /**
     * @param iterable<SplFileInfo> $files
     */
    public function beforeIteration(iterable $files): void;

    public function afterIteration(CognitiveMetricsCollection $metricsCollection): void;
}
