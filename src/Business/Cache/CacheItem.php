<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cache;

use Psr\Cache\CacheItemInterface;

/**
 * PSR-6 Cache Item implementation for file-based caching
 */
class CacheItem implements CacheItemInterface
{
    private string $key;
    private mixed $value;
    private bool $isHit;

    public function __construct(string $key, mixed $value, bool $isHit = false)
    {
        $this->key = $key;
        $this->value = $value;
        $this->isHit = $isHit;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function set(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    public function setExpiration(?int $expiration): static
    {
        // Not used in this file-based cache implementation
        // Cache validity is determined by file modification time and config hash
        return $this;
    }

    public function getExpiration(): ?int
    {
        // Not used in this file-based cache implementation
        return null;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        // Not used in this file-based cache implementation
        // Cache validity is determined by file modification time and config hash
        return $this;
    }

    public function expiresAfter(int|\DateInterval|null $time): static
    {
        // Not used in this file-based cache implementation
        // Cache validity is determined by file modification time and config hash
        return $this;
    }
}
