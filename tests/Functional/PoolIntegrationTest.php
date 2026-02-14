<?php

use PHPUnit\Framework\TestCase;

use Cache\IntegrationTests\CachePoolTest;
use JordJD\DOFileCachePSR6\CacheItemPool;

class PoolIntegrationTest extends CachePoolTest
{
    public function createCachePool()
    {
        $cacheItemPool = new CacheItemPool();
        $cacheItemPool->changeConfig(['cacheDirectory' => __DIR__.'/Data/']);
        return $cacheItemPool;
    }

    public function testExpiration()
    {
        if (getenv('TRAVIS')=='true') {
            $this->markTestSkipped('Travis CI does not seem to sleep correctly, so cache expiry can not be tested correctly.');
        }
        
        parent::testExpiration();
    }
}