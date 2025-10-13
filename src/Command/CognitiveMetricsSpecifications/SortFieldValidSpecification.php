<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

class SortFieldValidSpecification implements CognitiveMetricsSpecification
{
    private const SORTABLE_FIELDS = [
        'score',
        'halstead',
        'cyclomatic',
        'class',
        'method',
        'file',
        'lineCount',
        'argCount',
        'returnCount',
        'variableCount',
        'propertyCallCount',
        'ifCount',
        'ifNestingLevel',
        'elseCount',
        'lineCountWeight',
        'argCountWeight',
        'returnCountWeight',
        'variableCountWeight',
        'propertyCallCountWeight',
        'ifCountWeight',
        'ifNestingLevelWeight',
        'elseCountWeight'
    ];

    public function isSatisfiedBy(CognitiveMetricsCommandContext $context): bool
    {
        $sortBy = $context->getSortBy();

        // If no sort field is specified, validation passes
        if ($sortBy === null) {
            return true;
        }

        return in_array($sortBy, self::SORTABLE_FIELDS, true);
    }

    public function getErrorMessage(): string
    {
        return 'Invalid sort field provided.';
    }

    public function getErrorMessageWithContext(CognitiveMetricsCommandContext $context): string
    {
        return sprintf(
            'Invalid sort field "%s". Available fields: %s',
            $context->getSortBy(),
            implode(', ', self::SORTABLE_FIELDS)
        );
    }
}
