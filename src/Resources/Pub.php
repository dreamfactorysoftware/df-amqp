<?php

namespace DreamFactory\Core\AMQP\Resources;

use DreamFactory\Core\AMQP\Components\SwaggerDefinitions;
use DreamFactory\Core\Exceptions\BadRequestException;

class Pub extends \DreamFactory\Core\PubSub\Resources\Pub
{
    /**
     * @return array|mixed
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     */
    protected function handlePOST()
    {
        $data = $this->request->getPayloadData();
        $this->validatePublishData($data);
        $this->parent->getClient()->publish($data);

        return ['success' => true];
    }

    /**
     * @param $data
     *
     * @return bool
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     */
    protected function validatePublishData($data)
    {
        if (empty($data)) {
            throw new BadRequestException('Invalid or no data provided for publishing.');
        }

        if (!empty($message = array_get_or($data, ['message', 'msg']))) {
            if (is_array($message)) {
                if (empty(array_get_or($message, ['body', 'message', 'msg']))) {
                    throw new BadRequestException('No message body provided in message data object.');
                }
            }
        } else {
            throw new BadRequestException('No message provided in data to publish.');
        }

        if (empty(array_get_or($data, ['exchange', 'topic', 'queue', 'routing']))) {
            throw new BadRequestException('No topic/queue/exchange provided for message publishing.');
        }

        return true;
    }

    /** {@inheritdoc} */
    protected function getApiDocPaths()
    {
        $service = $this->getServiceName();
        $capitalized = camelize($service);
        $resourceName = strtolower($this->name);
        $path = '/' . $resourceName;
        $base = [
            $path => [
                'post' => [
                    'summary'     => 'Publish message',
                    'description' => 'Publishes message to AMQP server',
                    'operationId' => 'publish' . $capitalized . 'Message',
                    'requestBody' => [
                        'description' => 'Content - Message and exchange/queue to publish to',
                        'content'     => [
                            'application/json' => [
                                'schema' => [
                                    'type'       => 'object',
                                    'required'   => ['queue', 'message'],
                                    'properties' => [
                                        'exchange'    => SwaggerDefinitions::getExchangeDef(),
                                        'queue'       => SwaggerDefinitions::getQueueDef(),
                                        'routing_key' => SwaggerDefinitions::getRoutingKeyDef(),
                                        'message'     => SwaggerDefinitions::getMessageDef(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/Success']
                    ],
                ],
            ],
        ];

        return $base;
    }
}