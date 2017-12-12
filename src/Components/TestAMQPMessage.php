<?php

namespace DreamFactory\Core\AMQP\Components;

use DreamFactory\Core\AMQP\Contracts\AMQPMessageInterface;

class TestAMQPMessage implements AMQPMessageInterface
{
    /**
     * @param string $body
     * @param array $properties
     */
    public function __construct($body = '', $properties = array())
    {

    }
}