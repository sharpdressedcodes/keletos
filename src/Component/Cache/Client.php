<?php

namespace Keletos\Component\Cache;

abstract class Client extends \Keletos\Component\Component {

    protected $connected = false;
    protected $client = null;
    protected $host = null;
    protected $port = null;

    public function __construct(string $host, int $port) {

        parent::__construct();

        $this->connected = false;
        $this->host = $host;
        $this->port = $port;
    }

    public function __destruct() {
        if ($this->connected) {
            $this->close();
        }
    }

    public function isConnected() : bool {
        return $this->connected;
    }

    public function getHost() : string {
        return $this->host;
    }

    public function getPort() : int {
        return $this->port;
    }

    public abstract function connect() : bool;
    public abstract function close() : void;
    public abstract function get(string $key);
    public abstract function set(string $key, string $value, int $ttl /* seconds */) : bool;
}
