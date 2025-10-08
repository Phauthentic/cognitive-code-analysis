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
    ) {
    }

    /**
     * Convert the cache configuration to an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'directory' => $this->directory,
        ];
    }
}
