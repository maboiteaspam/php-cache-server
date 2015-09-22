<?php
error_reporting(E_ALL);

// be sure to have started the server by your own.
// IE: php server.php

require("lib/PhpCache/Client.php");
require("lib/PhpCache/Utils.php");

$address = "127.0.0.1";
$port = 8800;

$client = new PhpCache\Client();
$client->setAddress($address, $port);

if (!$client->open()) {
    PhpCache\Utils::stderr("Could not open connection to your server at address $address:$port\n");

} else {
    PhpCache\Utils::stderr("We are up and running, let s proceed !\n");

    $dataToCache = ["my", "stored", "data"];
    $dataId = "dataID";

    $client->sendCmd("store", [$dataId, $dataToCache]);
    $cachedData = $client->sendCmd("read", [$dataId]);

    var_dump($cachedData);

}

