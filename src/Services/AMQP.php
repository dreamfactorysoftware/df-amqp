<?php

namespace DreamFactory\Core\AMQP\Services;

use DreamFactory\Core\AMQP\Components\AMQPClient;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\AMQP\Resources\Pub;
use DreamFactory\Core\AMQP\Resources\Sub;
use DreamFactory\Core\PubSub\Services\PubSub;
use \Illuminate\Support\Arr;

class AMQP extends PubSub
{
    /** Queue type */
    const QUEUE_TYPE = 'AMQP';

    /** @type array Service Resources */
    protected static $resources = [
        Pub::RESOURCE_NAME => [
            'name'       => Pub::RESOURCE_NAME,
            'class_name' => Pub::class,
            'label'      => 'Publish'
        ],
        Sub::RESOURCE_NAME => [
            'name'       => Sub::RESOURCE_NAME,
            'class_name' => Sub::class,
            'label'      => 'Subscribe'
        ]
    ];

    /**
     * @param $config
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function setClient($config)
    {
        if (empty($config)) {
            throw new InternalServerErrorException('No service configuration found for AMQP service.');
        }

        $host = Arr::get($config, 'host');
        $port = Arr::get($config, 'port', 5672);
        $username = Arr::get($config, 'username');
        $password = Arr::get($config, 'password');
        $vhost = Arr::get($config, 'vhost');

        $this->client = new AMQPClient($host, $username, $password, $port, $vhost);
    }

    /** {@inheritdoc} */
    public function getQueueType()
    {
        return static::QUEUE_TYPE;
    }
}