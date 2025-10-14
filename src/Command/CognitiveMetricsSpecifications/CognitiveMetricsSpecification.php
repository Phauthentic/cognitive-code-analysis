<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

/**
 * @SuppressWarnings("LongClassName")
 */
interface CognitiveMetricsSpecification
{
    public function isSatisfiedBy(CognitiveMetricsCommandContext $context): bool;
    public function getErrorMessage(): string;
}
