<?php
namespace PhpCache;

class Server {
    public $host;
    public $port;
    public $socket;
    public $commands;

    public function setAddress ($host, $port) {
        $this->host = $host;
        $this->port = $port;
    }
    public function getError () {
        return socket_strerror(socket_last_error());
    }

    /**
     * @return bool
     *
     * The maximum number passed to the backlog parameter highly depends on the underlying platform.
     * On Linux, it is silently truncated to SOMAXCONN. On win32, if passed SOMAXCONN,
     * the underlying service provider responsible for the socket will set the backlog to a maximum reasonable value.
     * There is no standard provision to find out the actual backlog value on this platform.
     */
    public function start ($backlog=50) {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) return false;

        if (!socket_bind($socket, $this->host, $this->port)) return false;

        if (!socket_listen($socket, $backlog)) return false;

        $this->socket = $socket;
        return true;
    }
    public function stop () {
        if (!$this->socket) return false;
        socket_close($this->socket);
        return true;
    }
    public function listen (ServerCommands $commands) {
        if (!$this->socket) return false;
        $this->commands = $commands;

        do {
            // wait for a client to establish a connection
            $clientSocket = socket_accept($this->socket);

            if ($clientSocket===false) {
                // an attempt occurred, but failed for some reasons.
                Utils::stderr("socket_accept() failed: reason: " . socket_strerror(socket_last_error($this->socket)) . "\n");
            } else {
                // ok, connection is up and ready, let s read it.
                $dataRcv = $this->readClientSocket($clientSocket);
                // parse and execute the command
                $cmd = substr($dataRcv, 0, strpos($dataRcv, " "));
                if (method_exists($this->commands, $cmd)) {
                    $cmdArgs = unserialize(substr($dataRcv, strpos($dataRcv, " ")+1 ));
                    $response = call_user_func_array([$this->commands, $cmd], $cmdArgs);
                    // return response to the client.
                    $this->writeClientSocket($clientSocket, $response);
                }
            }
            // always close client connection
            socket_close($clientSocket);
        } while (true);

        return true;
    }

    private function readClientSocket ($clientSocket) {
        $data = NULL;
        $keepReading = true;
        do {
            $buf = socket_read($clientSocket, 2048, PHP_BINARY_READ);
            if ($buf===false || $buf==="") {
                // end of message
                $keepReading = false;
            } else {
                $data .= $buf;
            }
        } while ($keepReading);
        return $data===NULL?false:$data;
    }

    private function writeClientSocket ($clientSocket, $response) {
        $response = serialize($response);
        return socket_write($clientSocket, $response, strlen($response));
    }
}

class ServerCommands {
    public $dataStore = [];

    public function store ($id, $data) {
        $this->dataStore[$id] = $data;
    }
    public function delete ($id) {
        unset($this->dataStore[$id]);
    }
    public function read ($id) {
        return $this->dataStore[$id];
    }
}
