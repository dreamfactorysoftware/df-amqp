<?php

namespace DreamFactory\Core\AMQP\Resources;

use DreamFactory\Core\AMQP\Jobs\Subscribe;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\ForbiddenException;
use Illuminate\Support\Arr;

class Sub extends \DreamFactory\Core\PubSub\Resources\Sub
{
    /** {@inheritdoc} */
    protected function handlePOST()
    {
        $payload = $this->request->getPayloadData();
        static::validatePayload($payload);

        if (!$this->isJobRunning('AMQP')) {
            $jobCount = 0;
            foreach ($payload as $pl) {
                $job = new Subscribe($this->parent->getClient(), $pl);
                dispatch($job);
                $jobCount++;
            }

            return ['success' => true, 'job_count' => $jobCount];
        } else {
            throw new ForbiddenException(
                'System is currently running a subscription job. ' .
                'Please terminate the current process before subscribing to new topic(s)'
            );
        }
    }

    /** {@inheritdoc} */
    protected function handleDELETE()
    {
        $queue = $this->request->input('queue', $this->request->input('topic'));
        if (empty($queue)) {
            throw new BadRequestException('No queue/topic provided for terminating subscription.');
        }
        $payload = [
            'message' => Subscribe::TERMINATOR,
            'queue'   => $queue
        ];

        $this->parent->getClient()->publish($payload);

        return ["success" => true];
    }

    /**
     * @param $payload
     *
     * @throws \DreamFactory\Core\Exceptions\BadRequestException
     */
    protected static function validatePayload(&$payload)
    {
        if (empty($payload)) {
            throw new BadRequestException('No payload provided for subscriber/consumer.');
        }
        if (Arr::isAssoc($payload)) {
            $payload = [$payload];
        }

        foreach ($payload as $i => $pl) {
            if (!Subscribe::validatePayload($pl)) {
                if (count($payload) > 1) {
                    $msg = 'No queue/topic and/or service information provided in subscription payload[' . $i . '].';
                } else {
                    $msg = 'No queue/topic and/or service information provided in subscription payload.';
                }

                throw new BadRequestException($msg);
            }
        }
    }
}