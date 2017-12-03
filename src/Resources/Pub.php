<?php

namespace DreamFactory\Core\AMQP\Resources;

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
        if(empty($data)){
            throw new BadRequestException('Invalid or no data provided for publishing.');
        }

        if(!empty($message = array_get_or($data, ['message', 'msg']))){
            if(is_array($message)){
                if(empty(array_get_or($message, ['body', 'message', 'msg']))){
                    throw new BadRequestException('No message body provided in message data object.');
                }
            }
        } else {
            throw new BadRequestException('No message provided in data to publish.');
        }

        if(empty(array_get_or($data, ['routing_key', 'topic', 'queue', 'routing']))){
            throw new BadRequestException('No topic/queue/routing_key provided for message publishing.');
        }

        return true;
    }
}