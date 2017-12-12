<?php

namespace DreamFactory\Core\AMQP\Components;

use DreamFactory\Core\AMQP\Contracts\AMQPConnectionInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AMQPConnection extends AMQPStreamConnection implements AMQPConnectionInterface
{
}