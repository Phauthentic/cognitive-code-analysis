<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events;

use SplFileInfo;
use Throwable;

/**
 *
 */
class ParserFailed
{
    public function __construct(
        public readonly SplFileInfo $file,
        public readonly Throwable $throwable
    ) {
    }
}
