<?php

namespace JordJD\DOFileCachePSR6;

use Psr\Cache\CacheItemInterface;

class CacheItem implements CacheItemInterface
{
    private $key;
    private $value;
    private $expires = 0;
    public $isDeferred = false;
    public $deferredValue;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        if ($this->isHit()===false) {
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
        if ($this->expires !== 0 && $this->expires < time()) {
            return false;
        }

        return $this->value !== false;
    }

    public function set($value): static
    {
        if ($this->isDeferred) {
            $this->deferredValue = $value;
            return;
        }

        $this->value = $value;
        return $this;
    }

    public function prepareForSaveDeferred()
    {
        $this->isDeferred = true;
        if ($this->deferredValue) {
            $this->value = $this->deferredValue;
        }
        return $this;
    }

    public function expiresAt($expiration): static
    {
        if ($expiration==null) {
            $this->expires = 0;
        } else {
            $this->expires = $expiration->getTimestamp();
        }
        return $this;
    }

    public function expiresAfter($time): static
    {
        if (is_integer($time)) {
            $this->expires = time()+$time;
        }
        return $this;
    }

}
