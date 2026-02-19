<?php

namespace AppKit\Cache\Redis;

use AppKit\Health\HealthIndicatorInterface;
use AppKit\Health\HealthCheckResult;
use AppKit\Cache\CacheInterface;
use AppKit\Json\Json;

class RedisCache implements HealthIndicatorInterface, CacheInterface {
    private $client;

    function __construct($client) {
        $this -> client = $client;
    }

    public function checkHealth() {
        return new HealthCheckResult([
            'Redis client' => $this -> client
        ]);
    }

    public function has($key) {
        return (bool) $this -> command('exists', $key);
    }

    public function get($key) {
        $value = $this -> command('get', $key);
        if($value === null)
            return null;

        try {
            return Json::decode($value);
        } catch(Throwable $e) {
            throw new RedisCacheException(
                'Failed to decode value: ' . $e -> getMessage(),
                previous: $e
            );
        }
    }

    public function set($key, $value, $ttl = 0, $get = false) {
        $args = [ $key ];

        try {
            $args[] = Json::encode($value);
        } catch(Throwable $e) {
            throw new RedisCacheException(
                'Failed to encode value: ' . $e -> getMessage(),
                previous: $e
            );
        }

        if($get) {
            $args[] = 'GET';
        }
        if($ttl) {
            $args[] = 'EX';
            $args[] = $ttl;
        }

        $oldValue = $this -> client -> command('set', ...$args);
        if($get)
            return $oldValue;
    }

    public function increment($key, $by = 1) {
        return $this -> command('incrby', $key, $by);
    }

    public function decrement($key, $by = 1) {
        return $this -> command('decrby', $key, $by);
    }

    public function delete($key) {
        return $this -> command('delete', $key);
    }

    public function clear() {
        return $this -> command('flushdb');
    }

    private function command($command, ...$args) {
        try {
            return $this -> client -> command($command, ...$args);
        } catch(Throwable $e) {
            throw new RedisCacheException(
                "Command $command failed: " . $e -> getMessage(),
                previous: $e
            );
        }
    }
}
