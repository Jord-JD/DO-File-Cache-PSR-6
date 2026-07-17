<?php

namespace JordJD\DOFileCachePSR6;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

class CacheItem implements CacheItemInterface
{
    private $key;
    private $value;
    private $expires = 0;
    private $hit;

    public function __construct($key, $value, ?bool $hit = null)
    {
        $this->key = $key;
        $this->value = $value;
        $this->hit = $hit ?? $value !== false;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        if (!$this->isHit()) {
            return null;
        }

        return $this->value;
    }

    public function getExpires()
    {
        return $this->expires;
    }

    public function isHit(): bool
    {
        if ($this->expires !== 0 && $this->expires <= time()) {
            return false;
        }

        return $this->hit;
    }

    public function set($value): static
    {
        $this->value = $value;
        $this->hit = true;

        return $this;
    }

    public function prepareForSaveDeferred(): static
    {
        return $this;
    }

    public function expiresAt($expiration): static
    {
        if ($expiration === null) {
            $this->expires = 0;
        } elseif ($expiration instanceof DateTimeInterface) {
            $this->expires = $expiration->getTimestamp();
        } else {
            throw new CacheInvalidArgumentException('Expiration must be a DateTimeInterface instance or null.');
        }

        return $this;
    }

    public function expiresAfter($time): static
    {
        if ($time === null) {
            $this->expires = 0;
        } elseif (is_int($time)) {
            $this->expires = time() + $time;
        } elseif ($time instanceof DateInterval) {
            $this->expires = (new DateTimeImmutable())->add($time)->getTimestamp();
        } else {
            throw new CacheInvalidArgumentException('Expiration must be an integer, DateInterval instance, or null.');
        }

        return $this;
    }
}
