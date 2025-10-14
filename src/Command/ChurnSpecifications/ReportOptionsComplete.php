<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications;

class ReportOptionsComplete implements ChurnCommandSpecification
{
    public function isSatisfiedBy(ChurnCommandContext $context): bool
    {
        $reportType = $context->getReportType();
        $reportFile = $context->getReportFile();

        // Either both are provided or neither
        return ($reportType !== null && $reportFile !== null) ||
               ($reportType === null && $reportFile === null);
    }

    public function getErrorMessage(): string
    {
        return 'Both report type and file must be provided.';
    }
}
