<?php

namespace JordJD\DOFileCachePSR6;

use JordJD\DOFileCache\DOFileCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CacheItemPool implements CacheItemPoolInterface
{
    private const VALUE_MARKER = '__jordjd_psr6_value';

    private $doFileCache;
    private $deferredItems = [];

    public function __construct()
    {
        $this->doFileCache = new DOFileCache();
    }

    public function changeConfig(array $config)
    {
        return $this->doFileCache->changeConfig($config);
    }

    private function sanityCheckKey($key)
    {
        if (!is_string($key) || $key === '') {
            throw new CacheInvalidArgumentException('Cache keys must be non-empty strings.');
        }

        $invalidChars = ['{', '}', '(', ')', '/', '\\', '@', ':'];

        foreach ($invalidChars as $invalidChar) {
            if (stripos($key, $invalidChar) !== false) {
                throw new CacheInvalidArgumentException('Cache key contains a reserved character.');
            }
        }
    }

    public function getItem($key): CacheItemInterface
    {
        $this->sanityCheckKey($key);

        if (array_key_exists($key, $this->deferredItems)) {
            return $this->deferredItems[$key];
        }

        $stored = $this->doFileCache->get($key);

        if (is_array($stored) && array_key_exists(self::VALUE_MARKER, $stored)) {
            return new CacheItem($key, $stored[self::VALUE_MARKER], true);
        }

        return new CacheItem($key, $stored);
    }

    public function getItems(array $keys = []): iterable
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->getItem($key);
        }

        return $results;
    }

    public function hasItem($key): bool
    {
        $this->sanityCheckKey($key);

        return $this->getItem($key)->isHit();
    }

    public function clear(): bool
    {
        $this->deferredItems = [];
        return $this->doFileCache->flush();
    }

    public function deleteItem($key): bool
    {
        $this->sanityCheckKey($key);

        if (array_key_exists($key, $this->deferredItems)) {
            unset($this->deferredItems[$key]);
            return true;
        }

        $this->doFileCache->delete($key);

        return true;
    }

    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }

        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        if (!$item instanceof CacheItem) {
            throw new CacheInvalidArgumentException('Only cache items created by this pool can be saved.');
        }

        return $this->doFileCache->set(
            $item->getKey(),
            [self::VALUE_MARKER => $item->get()],
            $item->getExpires()
        );
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        if (!$item instanceof CacheItem) {
            throw new CacheInvalidArgumentException('Only cache items created by this pool can be deferred.');
        }

        $this->deferredItems[$item->getKey()] = $item->prepareForSaveDeferred();

        return true;
    }

    public function commit(): bool
    {
        $success = true;

        foreach ($this->deferredItems as $key => $item) {
            if ($this->save($item)) {
                unset($this->deferredItems[$key]);
            } else {
                $success = false;
            }
        }

        return $success;
    }

    public function __destruct()
    {
        $this->commit();
    }
}
