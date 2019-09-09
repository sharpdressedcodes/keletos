<?php

// https://github.com/phpredis/phpredis

namespace Keletos\Component\Cache\Redis;

class Client extends \Keletos\Component\Cache\Client {

    const DEFAULT_PORT = 6379;
    const DEFAULT_TIMEOUT = 2.5;

    public function __construct(string $host, int $port = self::DEFAULT_PORT) {

        self::$extensions = [
            'igbinary', // PECL
            'redis'     // PECL
        ];

        parent::__construct($host, $port);
    }

    public function connect(float $timeout = self::DEFAULT_TIMEOUT) : bool {

        if ($this->client) {
            $this->close();
        }

        $this->client = new \Redis();
        $this->connected = $this->client->connect($this->host, $this->port, $timeout);

        return $this->connected;
    }

    public function close() : void {

        if ($this->connected) {
            $this->connected = false;
            $this->client->close();
        }
    }

    /**
     * @param string $key The key
     * @return bool|string Returns the value as a string if successful, false otherwise.
     */
    public function get($key) {
        return $this->connected ? $this->client->get($key) : false;
    }

    /**
     * @param string $key The key
     * @param string $value The string to save
     * @param int $ttl (Optional) Time to live, in seconds. Defaults to 0
     * @return bool Returns true if successful
     */
    public function set(string $key, string $value, int $ttl = 0) : bool {
        return $this->connected ? $this->client->set($key, $value, $ttl) : false;
    }

}
