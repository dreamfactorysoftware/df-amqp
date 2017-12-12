<?php

namespace DreamFactory\Core\AMQP\Components;

use DreamFactory\Core\AMQP\Contracts\AMQPConnectionInterface;
use DreamFactory\Core\Testing\Faker;

class TestAMQPConnection extends Faker implements AMQPConnectionInterface
{
    /**
     * @param string $host
     * @param string $port
     * @param string $user
     * @param string $password
     * @param string $vhost
     * @param bool $insist
     * @param string $login_method
     * @param null $login_response
     * @param string $locale
     * @param float $connection_timeout
     * @param float $read_write_timeout
     * @param null $context
     * @param bool $keepalive
     * @param int $heartbeat
     */
    public function __construct(
        $host,
        $port,
        $user,
        $password,
        $vhost = '/',
        $insist = false,
        $login_method = 'AMQPLAIN',
        $login_response = null,
        $locale = 'en_US',
        $connection_timeout = 3.0,
        $read_write_timeout = 3.0,
        $context = null,
        $keepalive = false,
        $heartbeat = 0
    ) {
        $params = get_defined_vars();
        $this->setProperties($params);
    }

    public function channel($channel_id = null)
    {
        $params = get_defined_vars();
        $this->setProperties($params);

        return new TestAMQPChannel($this, $channel_id);
    }

    public function close($reply_code = 0, $reply_text = '', $method_sig = array(0, 0))
    {
        $params = get_defined_vars();
        $this->setProperties($params);
    }
}