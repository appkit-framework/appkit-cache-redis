<?php

namespace AppKit\Cache;

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
        return (bool) $this -> client -> exists($key);
    }

    public function get($key) {
        $value = $this -> client -> get($key);
        if($value === null)
            return null;
        return Json::decode($value);
    }

    public function set($key, $value, $ttl = 0, $get = false) {
        $args = [ $key, Json::encode($value) ];
        if($get) {
            $args[] = 'GET';
        }
        if($ttl) {
            $args[] = 'EX';
            $args[] = $ttl;
        }

        $oldValue = $this -> client -> set(...$args);
        if($get)
            return $oldValue;
    }

    public function increment($key, $by = 1) {
        return $this -> client -> incrby($key, $by);
    }

    public function decrement($key, $by = 1) {
        return $this -> client -> decrby($key, $by);
    }

    public function delete($key) {
        return $this -> client -> delete($key);
    }

    public function clear() {
        return $this -> client -> flushdb();
    }
}
