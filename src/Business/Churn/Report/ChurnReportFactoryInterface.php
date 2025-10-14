<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report;

/**
 * Interface for churn report factory.
 */
interface ChurnReportFactoryInterface
{
    /**
     * Create an exporter instance based on the report type.
     *
     * @param string $type The type of exporter to create (json, csv, html, markdown, svg-treemap)
     * @return ReportGeneratorInterface
     * @throws \InvalidArgumentException If the type is not supported
     */
    public function create(string $type): ReportGeneratorInterface;

    /**
     * Get list of supported exporter types.
     *
     * @return array<string>
     */
    public function getSupportedTypes(): array;

    /**
     * Check if a type is supported.
     *
     * @param string $type
     * @return bool
     */
    public function isSupported(string $type): bool;
}
