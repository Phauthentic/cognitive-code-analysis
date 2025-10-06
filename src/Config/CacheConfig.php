<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

/**
 * Configuration for cache settings
 */
class CacheConfig
{
    public function __construct(
        public bool $enabled,
        public string $directory,
        public bool $compression,
    ) {
    }
}
