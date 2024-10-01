<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

/**
 *
 */
class Config
{
    public CognitiveConfig $cognitive;

    public function __construct(CognitiveConfig $cognitive)
    {
        $this->cognitive = $cognitive;
    }
}
