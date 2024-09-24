<?php

/**
 * This class should lead to the following counts:
 *
 * - argCount = 5
 * - ifCount = 3
 * - ifNestingLevel = 2
 * - returnCount = 3
 * - elseCount = 1
 * - variableCount = 3 -- $this is also counted!
 * - propertyCallCount = 2
 */
class TestClassForCounts
{
    private string $property = 'Test';
    private string $property2 = 'Test';

    public function test(int $one, int $two, int $three, int $four, int $five): string
    {
        $var1 = 'foo';
        $var2 = 'bar';

        if (1 > 0) {
            $this->property = 'foo';
            $this->property2 = 'bar';
        } else {
            return 'foo';
        }

        if (2 === 3) {
            if ('this' !== 'that') {
                return 'this';
            }
        }

        return '';
    }
}
