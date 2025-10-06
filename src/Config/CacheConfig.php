<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Config;

/**
 * Configuration for cache settings
 */
class CacheConfig
{
    public function __construct(
        public readonly bool $enabled,
        public readonly string $directory,
        public readonly bool $compression,
    ) {
    }
}
