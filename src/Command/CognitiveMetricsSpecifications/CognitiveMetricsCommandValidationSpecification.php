<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

/**
 * @SuppressWarnings("LongClassName")
 */
interface CognitiveMetricsCommandValidationSpecification
{
    public function isSatisfiedBy(CognitiveMetricsCommandContext $context): bool;
    public function getErrorMessage(): string;
}
