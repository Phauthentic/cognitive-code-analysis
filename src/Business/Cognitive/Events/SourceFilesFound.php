<?php

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events;

use SplFileInfo;

/**
 *
 */
class SourceFilesFound
{
    /**
     * @param array<SplFileInfo> $files
     */
    public function __construct(
        public readonly array $files
    ) {
    }
}
