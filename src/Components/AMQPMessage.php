<?php

namespace DreamFactory\Core\AMQP\Components;

use DreamFactory\Core\AMQP\Contracts\AMQPMessageInterface;

class AMQPMessage extends \PhpAmqpLib\Message\AMQPMessage implements AMQPMessageInterface
{
}