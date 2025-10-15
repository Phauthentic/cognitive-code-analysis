<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCollection;

interface ReportGeneratorInterface
{
    public function export(SemanticCouplingCollection $couplings, string $filename): void;
}
