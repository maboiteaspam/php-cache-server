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

        if (!socket_connect($socket, $this->host, $this->port)) return false;

        $this->socket = $socket;
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
        if ($writtenBytes!==false) {
            socket_write($this->socket, "\0", strlen("\0"));
        }
        return $writtenBytes!==false;
    }
    public function read ($bufLen=2048) {
        if (!$this->socket) return false;

        $data = NULL;
        $keepReading = true;
        do {
            $buf = socket_read($this->socket, $bufLen, PHP_BINARY_READ);
            if ($buf===false || $buf==="" || substr($buf, -strlen("\0"))==="\0") {
                // end of message
                $keepReading = false;
            } else {
                $data .= $buf;
            }
        } while ($keepReading);
        $data = substr($data, 0, -strlen("\0"));
        return $data===null?false:$data;
    }
    public function sendCmd ($cmd, $data) {
        if (!$this->socket) return false;
        $data = serialize($data);
        if ($this->write("$cmd $data")) {
            return $this->read();
        }
        return false;
    }
}
