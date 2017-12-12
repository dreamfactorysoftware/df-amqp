<?php

namespace DreamFactory\Core\AMQP\Components;

use DreamFactory\Core\AMQP\Contracts\AMQPChannelInterface;
use DreamFactory\Core\Testing\Faker;

class TestAMQPChannel extends Faker implements AMQPChannelInterface
{

    /**
     * @param \DreamFactory\Core\AMQP\Contracts\AMQPConnectionInterface $connection
     * @param null $channel_id
     * @param bool $auto_decode
     * @throws \Exception
     */
    public function __construct($connection, $channel_id = null, $auto_decode = true)
    {
        $params = get_defined_vars();
        $this->setProperties($params);
    }

    public function basic_publish(
        $msg,
        $exchange = '',
        $routing_key = '',
        $mandatory = false,
        $immediate = false,
        $ticket = null
    ){
        $params = get_defined_vars();
        $this->setProperties($params);
    }

    public function basic_qos($prefetch_size, $prefetch_count, $a_global)
    {
        $params = get_defined_vars();
        $this->setProperties($params);
    }

    public function basic_consume(
        $queue = '',
        $consumer_tag = '',
        $no_local = false,
        $no_ack = false,
        $exclusive = false,
        $nowait = false,
        $callback = null,
        $ticket = null,
        $arguments = array()
    ){
        $params = get_defined_vars();
        $this->setProperties($params);
    }

    public function close($reply_code = 0, $reply_text = '', $method_sig = array(0, 0))
    {
        $params = get_defined_vars();
        $this->setProperties($params);
    }

    public function queue_bind($queue, $exchange, $routing_key = '', $nowait = false, $arguments = null, $ticket = null)
    {
        $params = get_defined_vars();
        $this->setProperties($params);
    }

    public function queue_declare(
        $queue = '',
        $passive = false,
        $durable = false,
        $exclusive = false,
        $auto_delete = true,
        $nowait = false,
        $arguments = null,
        $ticket = null
    ){
        $params = get_defined_vars();
        $this->setProperties($params);

        return [$queue, 0, 0];
    }

    public function exchange_declare(
        $exchange,
        $type,
        $passive = false,
        $durable = false,
        $auto_delete = true,
        $internal = false,
        $nowait = false,
        $arguments = null,
        $ticket = null
    ){
        $params = get_defined_vars();
        $this->setProperties($params);
    }
}