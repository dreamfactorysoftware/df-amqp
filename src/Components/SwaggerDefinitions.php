<?php

namespace DreamFactory\Core\AMQP\Components;

use DreamFactory\Core\PubSub\Components\SwaggerDefinitions as BaseDefinitions;

class SwaggerDefinitions extends BaseDefinitions
{
    /**
     * @return array
     */
    static public function getQueueDef()
    {
        return [
            'type'        => 'object',
            'required'    => ['name'],
            'properties'  => [
                'name'        => ['type' => 'string', 'description' => 'Queue name'],
                'passive'     => ['type' => 'boolean', 'default' => false],
                'durable'     => ['type' => 'boolean', 'default' => false],
                'exclusive'   => ['type' => 'boolean', 'default' => false],
                'auto_delete' => ['type' => 'boolean', 'default' => true],
                'no_wait'     => ['type' => 'boolean', 'default' => false],
                'argument'    => ['type' => 'string', 'default' => null],
                'ticket'      => ['type' => 'string', 'default' => null],
            ],
            'description' => 'Queue name. Optional when exchange is used. If not using any options then just provide queue name instead of the object.'
        ];
    }

    /**
     * @return array
     */
    static public function getQosDef()
    {
        return [
            'type'        => 'object',
            'properties'  => [
                'prefetch_size'  => ['type' => 'integer'],
                'prefetch_count' => ['type' => 'integer'],
                'a_global'       => ['type' => 'boolean']
            ],
            'description' => 'Set basic qos.'
        ];
    }

    /**
     * @return array
     */
    static public function getExchangeDef()
    {
        return [
            'type'        => 'object',
            'required'    => ['name'],
            'properties'  => [
                'name'        => ['type' => 'string', 'description' => 'Exchange name'],
                'type'        => ['type' => 'string', 'default' => 'fanout'],
                'passive'     => ['type' => 'boolean', 'default' => false],
                'durable'     => ['type' => 'boolean', 'default' => false],
                'internal'    => ['type' => 'boolean', 'default' => false],
                'auto_delete' => ['type' => 'boolean', 'default' => true],
                'no_wait'     => ['type' => 'boolean', 'default' => false],
                'argument'    => ['type' => 'string', 'default' => null],
                'ticket'      => ['type' => 'string', 'default' => null],
            ],
            'description' => 'Exchange name and type. If not using any options then just provide exchange name instead of the object.'
        ];
    }

    /**
     * @return array
     */
    static public function getMessageDef()
    {
        return [
            'type'        => 'object',
            'required'    => ['body'],
            'properties'  => [
                'body'                => [
                    'type'        => 'string',
                    'description' => 'Message body'
                ],
                'content_type'        => ['type' => 'string'],
                'content_encoding'    => ['type' => 'string'],
                'application_headers' => ['type' => 'string'],
                'delivery_mode'       => [
                    'type'        => 'integer',
                    'description' => '1 = persistent, 2 = non-persistent'
                ],
                'priority'            => ['type' => 'integer'],
                'correlation_id'      => ['type' => 'string'],
                'reply_to'            => ['type' => 'string'],
                'expiration'          => ['type' => 'string'],
                'message_id'          => ['type' => 'string'],
                'timestamp'           => ['type' => 'integer'],
                'type'                => ['type' => 'string'],
                'user_id'             => ['type' => 'string'],
                'app_id'              => ['type' => 'string'],
                'cluster_id'          => ['type' => 'string'],
            ],
            'description' => 'Payload message. If not using any message options then just provide your message string instead of the object.'
        ];
    }

    /**
     * @return array
     */
    static public function getRoutingKeyDef()
    {
        return ['type' => 'string', 'description' => 'Any routing key/binding key goes here.'];
    }
}