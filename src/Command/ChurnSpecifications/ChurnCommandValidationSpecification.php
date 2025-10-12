<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications;

/**
 * @SuppressWarnings("LongClassName")
 */
interface ChurnCommandValidationSpecification
{
    public function isSatisfiedBy(ChurnCommandContext $context): bool;
    public function getErrorMessage(): string;
}
