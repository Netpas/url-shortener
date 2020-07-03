<?php

declare(strict_types=1);

namespace App\Business;

use App\Business\Model\Shortener\Url;
use App\Common\Base;
use App\Env;
use Carbon\Carbon;
use Predis\Client as RedisClient;

/**
 * Caching Service
 */
class Caching extends Base
{
    /** @var string Cache key prefix */
    const CACHE_PREFIX = 'SHORTENER';

    /** @var int */
    const CACHE_TTL = 60 * 60;  // 60 minutes
    /** @var int */
    const MISSED_TTL = 60;      // 1 minute

    /** @var RedisClient */
    protected $redisClient;

    /**
     * 根据 token 获取 URL 目标
     */
    public function getTargetByToken(string $token): ?string
    {
        $store  = $this->getRedisClient();
        $key    = $this->makeCacheKey($token);
        $result = $store->get($key);

        return $result;
    }

    /**
     * 缓存 Url 目标
     */
    public function cacheUrl(Url $url)
    {
        $store = $this->getRedisClient();
        $key   = $this->makeCacheKey($url->token);
        $ttl   = $this->makeTtlSeconds($url->expired_at);
        $value = $url->target;
        $store->setex($key, $ttl, $value);
    }

    /**
     * 缓存这个短链不存在的信息
     */
    public function cacheMissed(string $token)
    {
        $store = $this->getRedisClient();
        $key   = $this->makeCacheKey($token);
        $ttl   = static::MISSED_TTL;
        $value = '';
        $store->setex($key, $ttl, $value);
    }

    protected function getRedisClient()
    {
        if (null === $this->redisClient) {
            $connectionParams  = Env::getSettings()['redis']['connections']['shortener'];
            $this->redisClient = new RedisClient($connectionParams);
        }

        return $this->redisClient;
    }

    protected function makeCacheKey(string $token): string
    {
        return static::CACHE_PREFIX.':'.$token;
    }

    protected function makeTtlSeconds(Carbon $expiredAt): int
    {
        $now       = time();
        $ts1       = $now + static::CACHE_TTL;
        $ts2       = $expiredAt->getTimestamp();
        $timestamp = min($ts1, $ts2);
        $seconds   = $timestamp - $now;

        return $seconds;
    }
}
