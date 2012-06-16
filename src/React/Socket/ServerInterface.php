<?php

namespace React\Socket;

use Evenement\EventEmitterInterface;

interface ServerInterface extends EventEmitterInterface
{
    public function listen($port, $host = '127.0.0.1', array $sslOptions = array());
    public function getPort();
    public function shutdown();
}
