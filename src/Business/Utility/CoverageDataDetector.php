<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Utility;

/**
 * Trait for detecting if coverage data is present in class data arrays.
 */
trait CoverageDataDetector
{
    /**
     * Check if any class has coverage data.
     *
     * @param array<string, mixed> $classes
     * @return bool
     */
    protected function hasCoverageData(array $classes): bool
    {
        foreach ($classes as $data) {
            if (array_key_exists('coverage', $data) && $data['coverage'] !== null) {
                return true;
            }
        }

        return false;
    }
}
