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
    public function setCommands ($commands) {
        $this->commands = $commands;
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
    public function open ($backlog=50) {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) return false;

        if (!socket_bind($socket, $this->host, $this->port)) return false;

        if (!socket_listen($socket, $backlog)) return false;

        $this->socket = $socket;
        return true;
    }
    public function close () {
        if (!$this->socket) return false;
        socket_close($this->socket);
        return true;
    }
    public function listen () {
        if (!$this->socket) return false;
        // wait for a client to establish a connection
        $clientSocket = null;

        do {
            if (!$clientSocket) {
                Utils::stderr("Waiting for client.... \n");
                $clientSocket = socket_accept($this->socket);
                if ($clientSocket) {
                    Utils::stderr("I got 1 !! \n");
                }
            }

            if ($clientSocket) {
                // ok, connection is up and ready, let s read it.
                Utils::stderr("waiting for data ... \n");
                $dataRcv = $this->readClientSocket($clientSocket);
                if ($dataRcv===false) {
                    Utils::stderr("Client may have disconnected. If not, please speak me better. Now I ignore you.\n");
                    $clientSocket = false;
                } else {
                    Utils::stderr("I got some data '".strlen($dataRcv)."', looks good !! \n");

                    // parse and execute the command
                    $cmd = substr($dataRcv, 0, strpos($dataRcv, " "));
                    if (method_exists($this->commands, $cmd)) {
                        $args = substr($dataRcv, strpos($dataRcv, " ")+1 );
                        Utils::stderr("Passing through '$cmd' with '$args' !! \n");
                        $response = call_user_func_array([$this->commands, $cmd], unserialize($args));
                        // return response to the client.
                        Utils::stderr("Replying response to the client... \n");
                        var_dump($response);
                        $this->writeClientSocket($clientSocket, $response);
                        Utils::stderr("All done ! \n");
                    } else {
                        Utils::stderr("That s unfortunate, my command server does not know about this command '$cmd' !! \n");
                    }
                }
            }
        } while (true);

        return true;
    }

    private function readClientSocket ($clientSocket) {
        $data = NULL;
        $keepReading = true;
        do {
            $buf = socket_read($clientSocket, 12, PHP_BINARY_READ);
            if ($buf===false || $buf==="" || substr($buf, -strlen("\0"))==="\0") {
                // end of message
                $keepReading = false;
            }
            $data .= $buf;
        } while ($keepReading);
        $data = substr($data, 0, -strlen("\0"));
        return $data===NULL?false:$data;
    }

    private function writeClientSocket ($clientSocket, $message) {
        $message = serialize($message);
        $writtenBytes = socket_write($clientSocket, $message, strlen($message));
        if ($writtenBytes!==false) {
            socket_write($clientSocket, "\0", strlen("\0"));
        }
        return $writtenBytes!==false;
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
