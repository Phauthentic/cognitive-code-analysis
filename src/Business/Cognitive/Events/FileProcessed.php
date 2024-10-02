<?php

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events;

use SplFileInfo;

/**
 *
 */
class FileProcessed
{
    public function __construct(
        public readonly SplFileInfo $file
    ) {
    }
}
