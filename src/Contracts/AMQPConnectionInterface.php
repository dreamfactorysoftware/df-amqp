<?php

namespace DreamFactory\Core\AMQP\Contracts;

interface AMQPConnectionInterface
{
    public function channel($channel_id = null);

    public function close($reply_code = 0, $reply_text = '', $method_sig = array(0, 0));
}