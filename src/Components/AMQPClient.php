<?php

namespace DreamFactory\Core\AMQP\Components;

use DreamFactory\Core\AMQP\Jobs\Subscribe;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\PubSub\Contracts\MessageQueueInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use DreamFactory\Core\Enums\Verbs;
use ServiceManager;
use Cache;
use Log;

class AMQPClient implements MessageQueueInterface
{
    /** @var string */
    protected $host;

    /** @var int */
    protected $port = 5672;

    /** @var null|string */
    protected $username = null;

    /** @var null|string */
    protected $password = null;

    /** @var string */
    protected $vhost = '/';

    /** @var string */
    protected $exchangeName = '';

    /** @var string */
    protected $queueName = '';

    /** @var \PhpAmqpLib\Connection\AMQPStreamConnection */
    protected $connection = null;

    /**
     * AMQPClient constructor.
     *
     * @param string $host
     * @param null   $username
     * @param null   $password
     * @param int    $port
     * @param string $vhost
     */
    public function __construct($host, $username = null, $password = null, $port = 5672, $vhost = '/')
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->vhost = $vhost;
    }

    /**
     * Sets AMQPStreamConnection.
     */
    protected function setConnection()
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
    }

    /**
     * Publishes message (Producer)
     *
     * @param array $data
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function publish(array $data)
    {
        $message = array_get_or($data, ['message', 'msg']);
        if (empty($message)) {
            throw new InternalServerErrorException('No message found for publishing.');
        }
        $amqpMsg = $this->getAMQPMessage($message);
        $channel = $this->setupChannel($data);
        $channel->basic_publish($amqpMsg, $this->exchangeName, $this->queueName);
    }

    /**
     * Subscribes to queue/topic (Consumer)
     *
     * @param array $data
     */
    public function subscribe(array $data)
    {
        Cache::forever(Subscribe::SUBSCRIPTION, json_encode($data, JSON_UNESCAPED_SLASHES));
        try {
            $channel = $this->setupChannel($data);
            $qos = array_get($data, 'qos');
            if (!empty($qos) && is_array($qos)) {
                $prefetchSize = array_get($qos, 'prefetch_size');
                $prefetchCount = array_get($qos, 'prefetch_count');
                $aGlobal = array_get($qos, 'a_global');
                $channel->basic_qos($prefetchSize, $prefetchCount, $aGlobal);
            }

            $consumerTag = array_get($data, 'consumer_tag', '');
            $noLocal = array_get($data, 'no_local', false);
            $noAck = array_get($data, 'no_ack', false);
            $exclusive = array_get($data, 'exclusive', false);
            $noWait = array_get_or($data, ['nowait', 'no_wait'], false);
            $callback = function ($msg) use ($data){
                Log::debug("[AMQP] Message received: " . $msg->body);
                $service = array_get($data, 'service');

                if (is_array($service) && $msg->body !== Subscribe::TERMINATOR) {
                    Log::debug('[AMQP] Triggering service: ' . json_encode($service, JSON_UNESCAPED_SLASHES));
                    // Retrieve service information
                    $endpoint = trim(array_get($service, 'endpoint'), '/');
                    $endpoint = str_replace('api/v2/', '', $endpoint);
                    $endpointArray = explode('/', $endpoint);
                    $serviceName = array_get($endpointArray, 0);
                    array_shift($endpointArray);
                    $resource = implode('/', $endpointArray);
                    $verb = strtoupper(array_get($service, 'verb', array_get($service, 'method', Verbs::POST)));
                    $params = array_get($service, 'parameter', array_get($service, 'parameters', []));
                    $header = array_get($service, 'header', array_get($service, 'headers', []));
                    $payload = array_get($service, 'payload', []);
                    $payload['message'] = $msg->body;

                    /** @var \DreamFactory\Core\Utility\ServiceResponse $rs */
                    $rs =
                        ServiceManager::handleRequest($serviceName, $verb, $resource, $params, $header, $payload, null,
                            false);
                    $content = $rs->getContent();
                    $content = (is_array($content)) ? json_encode($content) : $content;
                    Log::debug('[AMQP] Trigger response: ' . $content);
                }

                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

                if ($msg->body === Subscribe::TERMINATOR) {
                    Log::info('[AMQP] Terminate subscription signal received. Ending subscription job.');
                    Cache::forget(Subscribe::SUBSCRIPTION);

                    $msg->delivery_info['channel']->basic_cancel($msg->delivery_info['consumer_tag']);
                }
            };
            $ticket = array_get($data, 'ticket');
            $arguments = array_get($data, 'arguments', []);

            $channel->basic_consume(
                $this->queueName,
                $consumerTag,
                $noLocal,
                $noAck,
                $exclusive,
                $noWait,
                $callback,
                $ticket,
                $arguments
            );

            while (count($channel->callbacks)) {
                $channel->wait();
            }

            $channel->close();
            $this->connection->close();
        } catch (\Exception $e) {
            Log::error('[AMQP] Exception occurred. Terminating subscription. ' . $e->getMessage());
            Cache::forget(Subscribe::SUBSCRIPTION);
        }
    }

    /**
     * Sets up channel, exchange, queue, binding
     *
     * @param array $data
     *
     * @return \PhpAmqpLib\Channel\AMQPChannel
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function setupChannel(array $data)
    {
        $channel = $this->getChannel(array_get_or($data, ['channel', 'channel_id']));
        $exchange = array_get($data, 'exchange', '');
        if (!empty($exchange)) {
            $this->exchangeName = $this->declareExchange($channel, $exchange);
        }
        $queue = array_get_or($data, ['topic', 'queue'], '');
        if (!empty($queue)) {
            $this->queueName = $this->declareQueue($channel, $queue);
        }

        if (!empty($this->exchangeName) && !empty($this->queueName)) {
            $routingKey = array_get_or(
                $data,
                ['routing_key', 'routing_keys', 'routing', 'binding_key', 'binding_keys', 'binding'],
                ''
            );
            if (is_array($routingKey)) {
                foreach ($routingKey as $rk) {
                    $channel->queue_bind($this->queueName, $this->exchangeName, $rk);
                }
            } else {
                $channel->queue_bind($this->queueName, $this->exchangeName, $routingKey);
            }
        }

        return $channel;
    }

    /**
     * Declares/sets up queue
     *
     * @param \PhpAmqpLib\Channel\AMQPChannel $channel
     * @param string|array                    $queue
     *
     * @return string
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function declareQueue(&$channel, $queue)
    {
        $name = $queue;
        // Defaults
        $passive = false;
        $durable = false;
        $exclusive = false;
        $autoDelete = true;
        $noWait = false;
        $arguments = null;
        $ticket = null;

        if (is_array($queue)) {
            $name = array_get($queue, 'name', '');
            $passive = array_get($queue, 'passive', $passive);
            $durable = array_get($queue, 'durable', $durable);
            $exclusive = array_get($queue, 'internal', $exclusive);
            $autoDelete = array_get($queue, 'auto_delete', $autoDelete);
            $noWait = array_get_or($queue, ['nowait', 'no_wait'], $noWait);
            $arguments = array_get_or($queue, ['argument', 'arguments'], $arguments);
            $ticket = array_get($queue, 'ticket', $ticket);

            if (empty($name)) {
                throw new InternalServerErrorException('No queue name found in queue data.');
            }
        }
        $qInfo =
            $channel->queue_declare($name, $passive, $durable, $exclusive, $autoDelete, $noWait, $arguments, $ticket);

        if (!empty($qInfo) && is_array($qInfo)) {
            $name = $qInfo[0];
        }

        return $name;
    }

    /**
     * Returns the AMQPMessage object
     *
     * @param string|array $messageProp
     *
     * @return \PhpAmqpLib\Message\AMQPMessage
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function getAMQPMessage($messageProp)
    {
        if (is_array($messageProp)) {
            $body = array_get_or($messageProp, ['body', 'message', 'msg']);
            if (empty($body)) {
                throw new InternalServerErrorException('No message content/body found in message data.');
            }
            unset($messageProp['message'], $messageProp['msg'], $messageProp['body']);
        } else {
            $body = $messageProp;
            $messageProp = [];
        }
        if (is_array($body)) {
            $body = json_encode($body, JSON_UNESCAPED_SLASHES);
        }

        return new AMQPMessage($body, $messageProp);
    }

    /**
     * Returns AMQPChannel
     *
     * @param $channelId
     *
     * @return \PhpAmqpLib\Channel\AMQPChannel
     */
    protected function getChannel($channelId)
    {
        $this->setConnection();

        return $this->connection->channel($channelId);
    }

    /**
     * Declares/sets up exchange
     *
     * @param \PhpAmqpLib\Channel\AMQPChannel $channel
     * @param string|array                    $exchange
     *
     * @return string $name
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function declareExchange(&$channel, $exchange)
    {
        $name = $exchange;
        // Defaults
        $type = 'fanout';
        $passive = false;
        $durable = false;
        $autoDelete = true;
        $internal = false;
        $noWait = false;
        $arguments = null;
        $ticket = null;

        if (is_array($exchange)) {
            $name = array_get($exchange, 'name');
            $type = array_get($exchange, 'type', $type);
            $passive = array_get($exchange, 'passive', $passive);
            $durable = array_get($exchange, 'durable', $durable);
            $autoDelete = array_get($exchange, 'auto_delete', $autoDelete);
            $internal = array_get($exchange, 'internal', $internal);
            $noWait = array_get_or($exchange, ['nowait', 'no_wait'], $noWait);
            $arguments = array_get_or($exchange, ['argument', 'arguments'], $arguments);
            $ticket = array_get($exchange, 'ticket', $ticket);

            if (empty($name) || empty($type)) {
                throw new InternalServerErrorException('No exchange name and/or type found in exchange data.');
            }
        }
        $channel->exchange_declare(
            $name, $type, $passive, $durable, $autoDelete, $internal, $noWait, $arguments, $ticket
        );

        return $name;
    }
}