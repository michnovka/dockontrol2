<?php

declare(strict_types=1);

namespace App\Cache;

use App\Helper\RedisHelper;

readonly class RedisCameraCache
{
    public function __construct(private RedisHelper $redisHelper)
    {
    }

    /**
     * @param int<0, max> $millisecondTTL
     */
    public function saveCacheItem(string $key, string $value, int $millisecondTTL): bool
    {
        $redis = $this->redisHelper->getRedisInstance();
        $redis->psetex($key, $millisecondTTL, $value);

        return true;
    }

    public function getCacheItem(string $key): string|false
    {
        $redis = $this->redisHelper->getRedisInstance();

        $cameraData = $redis->get($key);
        if ($cameraData) {
            /** @var string $cameraData*/
            return $cameraData;
        }

        return false;
    }
}
