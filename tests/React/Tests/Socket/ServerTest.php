<?php

namespace React\Tests\Socket;

use React\Socket\Server;
use React\EventLoop\StreamSelectLoop;

class ServerTest extends TestCase
{
    private $loop;
    private $server;
    private $port;

    private function createLoop()
    {
        return new StreamSelectLoop();
    }

    /**
     * @covers React\Socket\Server::__construct
     * @covers React\Socket\Server::getPort
     */
    public function setUp()
    {
        $this->loop = $this->createLoop();
        $this->server = new Server($this->loop);
        $this->server->listen(0);

        $this->port = $this->server->getPort();
    }

    /**
     * @covers React\EventLoop\StreamSelectLoop::tick
     * @covers React\Socket\Server::handleConnection
     * @covers React\Socket\Server::createConnection
     */
    public function testConnection()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        $this->server->on('connect', $this->expectCallableOnce());
        $this->loop->tick();
    }

    /**
     * @covers React\Socket\Server::listen
     */
    public function testSSL()
    {
        $cert=file_get_contents(__DIR__.'/certificate.pem');
        $sslOptions=array(
            'local_cert' => __DIR__.'/certificate.pem',
            'allow_self_signed' => true,
            'verify_peer' => false
        );
        $sslServer=new Server($this->loop);

        $sslServer->listen(4096,'127.0.0.1',$sslOptions);

        //this currently does not work due to a error enabling crypto :-(
        //$client1 = stream_socket_client('ssl://localhost:4096');
        //fwrite($client1,"Encrypted Hello World!");

        //if you want to test this with testssl.php uncomment this line to have the server listen some time
        //to be able to start testssl.php
        //sleep(20);

        //$this->server->on('connect', $this->expectCallableOnce());

        $this->loop->tick();

    }

    /**
     * @covers React\EventLoop\StreamSelectLoop::tick
     * @covers React\Socket\Server::handleConnection
     * @covers React\Socket\Server::createConnection
     */
    public function testConnectionWithManyClients()
    {
        $client1 = stream_socket_client('tcp://localhost:'.$this->port);
        $client2 = stream_socket_client('tcp://localhost:'.$this->port);
        $client3 = stream_socket_client('tcp://localhost:'.$this->port);

        $this->server->on('connect', $this->expectCallableExactly(3));
        $this->loop->tick();
        $this->loop->tick();
        $this->loop->tick();
    }

    /**
     * @covers React\EventLoop\StreamSelectLoop::tick
     * @covers React\Socket\Connection::handleData
     */
    public function testDataWithNoData()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        $mock = $this->expectCallableNever();

        $this->server->on('connect', function ($conn) use ($mock) {
            $conn->on('data', $mock);
        });
        $this->loop->tick();
        $this->loop->tick();
    }

    /**
     * @covers React\EventLoop\StreamSelectLoop::tick
     * @covers React\Socket\Connection::handleData
     */
    public function testData()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        fwrite($client, "foo\n");

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with("foo\n");

        $this->server->on('connect', function ($conn) use ($mock) {
            $conn->on('data', $mock);
        });
        $this->loop->tick();
        $this->loop->tick();
    }

    public function testFragmentedMessage()
    {
        $client = stream_socket_client('tcp://localhost:' . $this->port);

        fwrite($client, "Hello World!\n");

        $mock = $this->createCallableMock();
        $mock
            ->expects($this->once())
            ->method('__invoke')
            ->with("He");

        $this->server->on('connect', function ($conn) use ($mock) {
            $conn->bufferSize = 2;
            $conn->on('data', $mock);
        });
        $this->loop->tick();
        $this->loop->tick();
    }

    /**
     * @covers React\EventLoop\StreamSelectLoop::tick
     */
    public function testDisconnectWithoutDisconnect()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        $mock = $this->expectCallableNever();

        $this->server->on('connect', function ($conn) use ($mock) {
            $conn->on('end', $mock);
        });
        $this->loop->tick();
        $this->loop->tick();
    }

    /**
     * @covers React\EventLoop\StreamSelectLoop::tick
     * @covers React\Socket\Connection::end
     */
    public function testDisconnect()
    {
        $client = stream_socket_client('tcp://localhost:'.$this->port);

        fclose($client);

        $mock = $this->expectCallableOnce();

        $this->server->on('connect', function ($conn) use ($mock) {
            $conn->on('end', $mock);
        });
        $this->loop->tick();
        $this->loop->tick();
    }

    /**
     * @covers React\Socket\Server::shutdown
     */
    public function tearDown()
    {
        if ($this->server) {
            $this->server->shutdown();
        }
    }
}
