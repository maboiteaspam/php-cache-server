<?php
error_reporting(E_ALL);

// be sure to have started the server by your own.
// IE: php server.php

require("lib/PhpCache/Server.php");
require("lib/PhpCache/Utils.php");

$address = "127.0.0.1";
$port = 8800;

$server = new PhpCache\Server();
$server->setAddress($address, $port);
$server->setCommands(new \PhpCache\ServerCommands());

if (!$server->open()) {
    PhpCache\Utils::stderr("Could not open socket at address $address:$port\n");

} else {
    PhpCache\Utils::stderr("I am up and running, send me some commands!\n");
    $server->listen();
}
