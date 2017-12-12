<?php

use DreamFactory\Core\AMQP\Components\AMQPClient;
use DreamFactory\Core\AMQP\Contracts\AMQPConnectionInterface;
use DreamFactory\Core\AMQP\Contracts\AMQPChannelInterface;

class AMQPClientTest extends \DreamFactory\Core\Testing\TestCase
{
    /** @var AMQPClient */
    protected $client;

    private $host = 'localhost';
    private $port = '5672';
    private $user = 'foobar';
    private $pass = 'secret';
    private $vhost = '/';

    public function setUp()
    {
        parent::setUp();

        $this->client = $this->getNewClient();
    }

    public function tearDown()
    {
        unset($this->client);

        parent::tearDown();
    }

    public function getNewClient()
    {
        return new AMQPClient($this->host, $this->user, $this->pass, $this->port, $this->vhost);
    }

    public function testClient()
    {
        $host = $this->getNonPublicProperty($this->client, 'host');
        $port = $this->getNonPublicProperty($this->client, 'port');
        $user = $this->getNonPublicProperty($this->client, 'username');
        $pass = $this->getNonPublicProperty($this->client, 'password');
        $vhost = $this->getNonPublicProperty($this->client, 'vhost');

        $this->assertEquals($this->host, $host);
        $this->assertEquals($this->port, $port);
        $this->assertEquals($this->user, $user);
        $this->assertEquals($this->pass, $pass);
        $this->assertEquals($this->vhost, $vhost);
    }

    public function testConnectionChannel()
    {
        $channel = $this->invokeMethod($this->client, 'getChannel', [null]);
        $connection = $this->getNonPublicProperty($this->client, 'connection');

        $this->assertTrue(($connection instanceof AMQPConnectionInterface));
        $this->assertTrue(($channel instanceof AMQPChannelInterface));
    }

    public function testChannelQueue()
    {
        $data = [
            'queue'   => 'test',
            'service' => ['endpoint' => 'db/_table', 'verb' => 'GET']
        ];
        /** @var AMQPChannel $channel */
        $channel = $this->invokeMethod($this->client, 'setupChannel', [$data, AMQPClient::MODE_SUB]);

        $this->assertTrue(($channel instanceof AMQPChannelInterface));
        $this->assertEquals('test', $this->getNonPublicProperty($this->client, 'queueName'));
    }

    public function testChannelQueue2()
    {
        $data = [
            'queue'   => [
                'name'        => 'tasks',
                'passive'     => true,
                'durable'     => true,
                'exclusive'   => true,
                'auto_delete' => false,
                'no_wait'     => true,
                'arguments'   => 'foobar',
                'ticket'      => '1234'
            ],
            'qos'     => [
                'prefetch_size'  => 2,
                'prefetch_count' => 1,
                'a_global'       => true
            ],
            'service' => ['endpoint' => 'db/_table', 'verb' => 'GET']
        ];
        /** @var AMQPChannel $channel */
        $channel = $this->invokeMethod($this->client, 'setupChannel', [$data, AMQPClient::MODE_SUB]);

        $this->assertTrue(($channel instanceof AMQPChannelInterface));
        $this->assertEquals('tasks', $this->getNonPublicProperty($this->client, 'queueName'));
        $this->assertTrue($channel->queue_declare['durable']);
        $this->assertTrue($channel->queue_declare['passive']);
        $this->assertTrue($channel->queue_declare['exclusive']);
        $this->assertFalse($channel->queue_declare['auto_delete']);
        $this->assertTrue($channel->queue_declare['nowait']);
        $this->assertEquals('foobar', $channel->queue_declare['arguments']);
        $this->assertEquals('1234', $channel->queue_declare['ticket']);
        $this->assertEquals(2, $channel->basic_qos['prefetch_size']);
        $this->assertEquals(1, $channel->basic_qos['prefetch_count']);
        $this->assertTrue($channel->basic_qos['a_global']);
    }

    public function testChannelExchange()
    {
        $data = [
            'exchange' => 'works',
            'service'  => ['endpoint' => 'db/_table', 'verb' => 'GET']
        ];
        /** @var AMQPChannel $channel */
        $channel = $this->invokeMethod($this->client, 'setupChannel', [$data, AMQPClient::MODE_SUB]);
        $this->assertTrue(($channel instanceof AMQPChannelInterface));
        $this->assertEquals('works', $this->getNonPublicProperty($this->client, 'exchangeName'));
        $this->assertEquals('works', $channel->exchange_declare['exchange']);
        $this->assertEquals('fanout', $channel->exchange_declare['type']);
    }

    public function testChannelExchange2()
    {
        $data = [
            'exchange' => [
                'name'        => 'works',
                'type'        => 'direct',
                'passive'     => true,
                'durable'     => true,
                'internal'    => true,
                'auto_delete' => false,
                'no_wait'     => true,
                'arguments'   => 'foobar',
                'ticket'      => '1234'
            ],
            'service'  => ['endpoint' => 'db/_table', 'verb' => 'GET']
        ];
        /** @var AMQPChannel $channel */
        $channel = $this->invokeMethod($this->client, 'setupChannel', [$data, AMQPClient::MODE_SUB]);
        $this->assertTrue(($channel instanceof AMQPChannelInterface));
        $this->assertEquals('works', $this->getNonPublicProperty($this->client, 'exchangeName'));
        $this->assertEquals('works', $channel->exchange_declare['exchange']);
        $this->assertEquals('direct', $channel->exchange_declare['type']);
        $this->assertTrue($channel->exchange_declare['durable']);
        $this->assertTrue($channel->exchange_declare['passive']);
        $this->assertTrue($channel->exchange_declare['internal']);
        $this->assertFalse($channel->exchange_declare['auto_delete']);
        $this->assertTrue($channel->exchange_declare['nowait']);
        $this->assertEquals('foobar', $channel->exchange_declare['arguments']);
        $this->assertEquals('1234', $channel->exchange_declare['ticket']);
    }

    public function testChannelExchange3()
    {
        $data = [
            'exchange'    => 'works',
            'queue'       => 'task',
            'routing_key' => 'q1',
            'service'     => ['endpoint' => 'db/_table', 'verb' => 'GET']
        ];
        /** @var AMQPChannel $channel */
        $channel = $this->invokeMethod($this->client, 'setupChannel', [$data, AMQPClient::MODE_SUB]);
        $this->assertTrue(($channel instanceof AMQPChannelInterface));
        $this->assertEquals('works', $this->getNonPublicProperty($this->client, 'exchangeName'));
        $this->assertEquals('works', $channel->exchange_declare['exchange']);
        $this->assertEquals('fanout', $channel->exchange_declare['type']);
        $this->assertEquals('q1', $channel->queue_bind['routing_key']);
    }
}