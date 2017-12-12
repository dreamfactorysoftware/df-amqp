<?php

namespace DreamFactory\Core\AMQP\Components;

class AMQPFactory
{
    /**
     * @param        $host
     * @param        $port
     * @param        $username
     * @param        $password
     * @param string $vhost
     *
     * @return \DreamFactory\Core\AMQP\Contracts\AMQPConnectionInterface
     */
    static public function Connection($host, $port, $username, $password, $vhost = '/')
    {
        if(env('APP_ENV') === 'testing'){
            return new TestAMQPConnection($host, $port, $username, $password, $vhost);
        } else {
            return new AMQPConnection($host, $port, $username, $password, $vhost);
        }
    }

    /**
     * @param string $body
     * @param array  $properties
     *
     * @return \DreamFactory\Core\AMQP\Contracts\AMQPMessageInterface
     */
    static public function Message($body = '', $properties = [])
    {
        if(env('APP_ENV') === 'testing'){
            return new TestAMQPMessage($body, $properties);
        } else {
            return new AMQPMessage($body, $properties);
        }
    }
}