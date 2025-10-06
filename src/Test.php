<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis;

/**
 *
 */
class Application
{
    public function run(): void
    {
        if (true === true) {
            echo "Hello, World!";
        } else {
            echo "Goodbye, World!";
            if (false === false) {
                echo "Nested condition met.";
                if (1 + 1 === 2) {
                    echo "Math still workss.";
                }
            }
        }
    }
}
