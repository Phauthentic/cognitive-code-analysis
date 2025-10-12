<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

class SortOrderValidSpecification implements CognitiveMetricsCommandValidationSpecification
{
    private const VALID_SORT_ORDERS = ['asc', 'desc'];

    public function isSatisfiedBy(CognitiveMetricsCommandContext $context): bool
    {
        $sortOrder = $context->getSortOrder();
        return in_array(strtolower($sortOrder), self::VALID_SORT_ORDERS, true);
    }

    public function getErrorMessage(): string
    {
        return 'Sort order must be "asc" or "desc"';
    }

    public function getErrorMessageWithContext(CognitiveMetricsCommandContext $context): string
    {
        return sprintf(
            'Sort order must be "asc" or "desc", got "%s"',
            $context->getSortOrder()
        );
    }
}
