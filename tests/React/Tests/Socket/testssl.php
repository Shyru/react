#!/usr/bin/php
<?php

error_reporting(-1);

//simple test SSL-client that tries to connect to 4096
//this works perfectly fine when using server.php but not when trying to connect to the server
//in ServerTest.php :-(

$clientContext=stream_context_create(array("ssl"=>array(
		'allow_self_signed'=>true,
		'verify_peer' => false
	)));
$connection = stream_socket_client('ssl://localhost:4096',$errcode,$errstr,2,STREAM_CLIENT_CONNECT,$clientContext);
//$connection = stream_socket_client('ssl://localhost:4096',$errcode,$errstr);
if ($connection)
{
	echo "\n\n************\nConnected!\n\n";
	sleep(1);
	echo stream_get_contents($connection);
}
else
{
	echo "\n\nError occured:\n$errcode\n$errstr\n\n";
}




