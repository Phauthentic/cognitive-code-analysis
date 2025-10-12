<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter;

interface DataExporterInterface
{
    /**
     * @param array<string, array<string, mixed>> $classes
     */
    public function export(array $classes, string $filename): void;
}
