<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

use SplFileInfo;

/**
 * @deprecated Nothing calls these hooks since the collector switched to events. Will be removed in 2.0.
 *             The collector dispatches the SourceFilesFound and FileProcessed events instead.
 * @see https://github.com/Phauthentic/cognitive-code-analysis/issues/93
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
