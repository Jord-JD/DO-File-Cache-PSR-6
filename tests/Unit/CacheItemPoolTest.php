<?php

declare(strict_types=1);

use JordJD\DOFileCachePSR6\CacheInvalidArgumentException;
use JordJD\DOFileCachePSR6\CacheItemPool;
use PHPUnit\Framework\TestCase;

final class CacheItemPoolTest extends TestCase
{
    private string $cacheDir;
    private CacheItemPool $pool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir() . '/do-file-cache-psr6-' . uniqid('', true) . '/';
        mkdir($this->cacheDir, 0777, true);

        $this->pool = new CacheItemPool();
        $this->pool->changeConfig(['cacheDirectory' => $this->cacheDir]);
        $this->pool->clear();
    }

    protected function tearDown(): void
    {
        $this->pool->clear();
        $this->removeDir($this->cacheDir);
        parent::tearDown();
    }

    public function testSaveAndFetchItem(): void
    {
        $item = $this->pool->getItem('example');
        $item->set('value');
        $this->pool->save($item);

        $fetched = $this->pool->getItem('example');
        $this->assertTrue($fetched->isHit());
        $this->assertSame('value', $fetched->get());
    }

    public function testDeleteItemRemovesValue(): void
    {
        $item = $this->pool->getItem('example');
        $item->set('value');
        $this->pool->save($item);
        $this->assertTrue($this->pool->hasItem('example'));

        $this->pool->deleteItem('example');
        $this->assertFalse($this->pool->hasItem('example'));
    }

    public function testDeferredCommitPersistsValue(): void
    {
        $item = $this->pool->getItem('deferred-key');
        $item->set('deferred-value');
        $this->pool->saveDeferred($item);
        $this->pool->commit();

        $freshPool = new CacheItemPool();
        $freshPool->changeConfig(['cacheDirectory' => $this->cacheDir]);

        $fetched = $freshPool->getItem('deferred-key');
        $this->assertTrue($fetched->isHit());
        $this->assertSame('deferred-value', $fetched->get());
    }

    public function testInvalidKeyThrowsException(): void
    {
        $this->expectException(CacheInvalidArgumentException::class);
        $this->pool->getItem('invalid/key');
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
