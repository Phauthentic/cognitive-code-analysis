<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCollection;

interface SemanticCouplingReportFactoryInterface
{
    public function create(string $reportType): ReportGeneratorInterface;

    public function getAvailableReportTypes(): array;

    public function isReportTypeSupported(string $reportType): bool;
}
