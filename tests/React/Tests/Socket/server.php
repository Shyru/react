#!/usr/bin/php
<?php

//Sample SSL-based TCP server that listens on 4096 with certificate.pem and sends a hello world to any new clients connecting

error_reporting(-1);

$sslOptions=array(
	'local_cert' => __DIR__.'/certificate.pem',
	'allow_self_signed' => true,
	'verify_peer' => false
);

$serverContext = stream_context_create(array("ssl" => $sslOptions));

$socket = stream_socket_server("ssl://127.0.0.1:4096", $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $serverContext);

while ($conn = stream_socket_accept($socket)) {
	fwrite($conn, "Hello encrypted world!\n");
	fclose($conn);
}
