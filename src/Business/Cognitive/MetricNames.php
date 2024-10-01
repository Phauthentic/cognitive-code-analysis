<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

/**
 * Enum to represent the metric names.
 */
enum MetricNames: string
{
    case LINE_COUNT = 'lineCount';
    case ARG_COUNT = 'argCount';
    case RETURN_COUNT = 'returnCount';
    case VARIABLE_COUNT = 'variableCount';
    case PROPERTY_CALL_COUNT = 'propertyCallCount';
    case IF_COUNT = 'ifCount';
    case IF_NESTING_LEVEL = 'ifNestingLevel';
    case ELSE_COUNT = 'elseCount';
}
