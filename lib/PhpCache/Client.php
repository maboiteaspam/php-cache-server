<?php
namespace PhpCache;

class Client {
    public $host;
    public $port;
    public $socket;

    public function setAddress ($host, $port) {
        $this->host = $host;
        $this->port = $port;
    }
    public function getError () {
        return socket_strerror(socket_last_error());
    }
    public function open () {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) return false;

        $result = socket_connect($socket, $this->host, $this->port);
        if (!$result) return false;

        $this->socket = $result;
        return true;
    }
    public function close () {
        if (!$this->socket) return false;
        socket_close($this->socket);
        return true;
    }
    public function write ($data) {
        if (!$this->socket) return false;
        $writtenBytes = socket_write($this->socket, $data, strlen($data));
        return $writtenBytes!==false;
    }
    public function read ($bufLen=2048) {
        if (!$this->socket) return false;
        $read = null;
        while ($data = socket_read($this->socket, $bufLen, PHP_BINARY_READ)) {
            $read .= $data;
        }
        return $read===null?false:$read;
    }
    public function sendCmd ($cmd, $data) {
        if (!$this->socket) return false;
        if ($this->write("$cmd $data")) {
            return $this->read();
        }
        return false;
    }
}
