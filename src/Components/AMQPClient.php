<?php

namespace DreamFactory\Core\AMQP\Components;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\PubSub\Contracts\MessageQueueInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AMQPClient implements MessageQueueInterface
{
    protected $host = null;

    protected $port = 5672;

    protected $username = null;

    protected $password = null;

    protected $vhost = '/';

    /** @var \PhpAmqpLib\Connection\AMQPStreamConnection */
    protected $connection = null;

    public function __construct($host, $username = null, $password = null, $port = 5672, $vhost = '/')
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->vhost = $vhost;
    }

    /**
     * @return \PhpAmqpLib\Connection\AMQPStreamConnection
     */
    public function getConnection()
    {
        if (empty($this->connection)) {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->username,
                $this->password,
                $this->vhost
            );
        }

        return $this->connection;
    }

    /**
     * @param array $data
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function publish(array $data)
    {
        $message = array_get_or($data, ['message', 'msg']);
        if(empty($message)){
            throw new InternalServerErrorException('No message found for publishing.');
        }
        $amqpMsg = $this->getAMQPMessage($message);

        $channelId = array_get_or($data, ['channel', 'channel_id']);
        $channel = $this->getChannel($channelId);

        $exchange = array_get($data, 'exchange', '');
        $exchangeName = '';
        if(!empty($exchange)){
            $exchangeName = $this->declareExchange($channel, $exchange);
        }
        $routingKey = array_get_or($data, ['routing_key', 'topic', 'queue', 'routing'], '');
        $channel->basic_publish($amqpMsg, $exchangeName, $routingKey);
    }

    /**
     * @param mixed $messageProp
     *
     * @return \PhpAmqpLib\Message\AMQPMessage
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function getAMQPMessage($messageProp)
    {
        if(is_array($messageProp)){
            $body = array_get_or($messageProp, ['body', 'message', 'msg']);
            if(empty($body)){
                throw new InternalServerErrorException('No message content/body found in message data.');
            }
            unset($messageProp['message'], $messageProp['msg'], $messageProp['body']);
        } else {
            $body = $messageProp;
            $messageProp = [];
        }

        return new AMQPMessage($body, $messageProp);
    }

    protected function getChannel($channelId)
    {
        $conn = $this->getConnection();
        return $conn->channel($channelId);
    }

    /**
     * @param \PhpAmqpLib\Channel\AMQPChannel $channel
     * @param mixed $exchange
     *
     * @return string $name
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function declareExchange(&$channel, $exchange)
    {
        if(is_array($exchange)){
            $name = array_get($exchange, 'name');
            $type = array_get($exchange, 'type');

            if(empty($name) || empty($type)){
                throw new InternalServerErrorException('No exchange name and/or type found in exchange data.');
            }
        } else {
            $name = $exchange;
            $type = 'fanout';
        }
        $channel->exchange_declare($name, $type);

        return $name;
    }

    public function subscribe(array $payload)
    {
        // TODO: Implement subscribe() method.
    }
}