<?php

declare(strict_types=1);

namespace App\Helper;

use Redis;
use RedisException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RedisHelper
{
    public bool $connected = false;

    public Redis $redis;

    private string $ip;
    private int $port;

    private string $redisPassword;

    public function __construct(private readonly ParameterBagInterface $parameterBag)
    {
        if (
            !$this->parameterBag->has('redis_ip') ||
            !$this->parameterBag->has('redis_port') ||
            !$this->parameterBag->has('redis_password')
        ) {
            throw new RedisException('Required parameters are missing.');
        }

        $ip = $this->parameterBag->get('redis_ip');
        $port = $this->parameterBag->get('redis_port');
        $redisPassword = $this->parameterBag->get('redis_password');

        if (!is_string($ip) || !is_string($redisPassword)) {
            throw new RedisException('IP address or password must be a string.');
        }

        if (is_string($port)) {
            $port = (int) $port;
        }

        if (!is_int($port)) {
            throw new RedisException('Port must be an integer.');
        }

        $this->ip = $ip;
        $this->port = $port;
        $this->redisPassword = $redisPassword;
    }

    /**
     * @throws RedisException
     */
    public function getRedisInstance(): Redis
    {
        return $this->connect();
    }

    public function clear(): void
    {
        if ($this->connected) {
            $this->redis->close();
            $this->connected = false;
        }
    }

    /**
     * @throws RedisException
     */
    private function connect(): Redis
    {
        if ($this->connected) {
            return $this->redis;
        }

        $this->redis = new Redis();

        try {
            $this->redis->connect($this->ip, $this->port);
            $this->redis->auth($this->redisPassword);
        } catch (RedisException $e) {
            throw new RedisException("Failed to connect or authenticate with redis: " . $e->getMessage(), $e->getCode(), $e);
        }

        $this->connected = true;

        return $this->redis;
    }
}
