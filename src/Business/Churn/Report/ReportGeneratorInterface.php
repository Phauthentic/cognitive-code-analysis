<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report;

/**
 *
 */
interface ReportGeneratorInterface
{
    /**
     * @param array<string, array<string, mixed>> $classes
     */
    public function export(array $classes, string $filename): void;
}
