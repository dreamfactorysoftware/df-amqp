<?php

namespace DreamFactory\Core\AMQP\Jobs;

use DreamFactory\Core\PubSub\Jobs\BaseSubscriber;

class Subscribe extends BaseSubscriber
{
    /** {@inheritdoc} */
    public static function validatePayload(array $payload)
    {
        if ((isset($payload['queue']) || isset($payload['topic']) || isset($payload['exchange'])) &&
            isset($payload['service'])
        ) {
            return true;
        }

        return false;
    }

    /** {@inheritdoc} */
    public function handle()
    {
        $this->client->subscribe($this->payload);
    }
}