<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn\Report;

use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

/**
 * Test-specific config class for testing
 */
class TestCognitiveConfig extends CognitiveConfig
{
    public function __construct(
        array $excludeFilePatterns = [],
        array $excludePatterns = [],
        array $metrics = [],
        bool $showOnlyMethodsExceedingThreshold = false,
        float $scoreThreshold = 0.5,
        bool $showHalsteadComplexity = false,
        bool $showCyclomaticComplexity = false,
        bool $groupByClass = false,
        bool $showDetailedCognitiveMetrics = true,
        array $customReporters = []
    ) {
        parent::__construct(
            excludeFilePatterns: $excludeFilePatterns,
            excludePatterns: $excludePatterns,
            metrics: $metrics,
            showOnlyMethodsExceedingThreshold: $showOnlyMethodsExceedingThreshold,
            scoreThreshold: $scoreThreshold,
            showHalsteadComplexity: $showHalsteadComplexity,
            showCyclomaticComplexity: $showCyclomaticComplexity,
            groupByClass: $groupByClass,
            showDetailedCognitiveMetrics: $showDetailedCognitiveMetrics,
            customReporters: $customReporters
        );
    }
}
