<?php

namespace DreamFactory\Core\AMQP\Resources;

use DreamFactory\Core\AMQP\Components\SwaggerDefinitions;
use DreamFactory\Core\AMQP\Jobs\Subscribe;
use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\ForbiddenException;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\PubSub\Jobs\BaseSubscriber;
use Illuminate\Support\Arr;
use DB;

class Sub extends \DreamFactory\Core\PubSub\Resources\Sub
{
    /** {@inheritdoc} */
    protected function handlePOST()
    {
        $payload = $this->request->getPayloadData();
        static::validatePayload($payload);

        if (!$this->isJobRunning()) {
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

    /**
     * {@inheritdoc}
     */
    protected function handleDELETE()
    {
        // Put a terminate flag in the cache to terminate the subscription job.
        //Cache::put(BaseSubscriber::TERMINATOR, true, config('df.default_cache_ttl', 300));
        $jobs = $this->getSubscriptionJobs();

        if (count($jobs) === 0) {
            throw new NotFoundException('Could not find any subscription job(s) to delete');
        }

        foreach ($jobs as $job) {
            if ($job->attempts === 0) {
                DB::table('jobs')->delete($job->id);
            } else {
                $obj = unserialize(array_get(json_decode($job->payload, true), 'data.command'));
                $payload = $obj->getPayload();
                $channel = array_get_or($payload, ['channel', 'channel_id']);
                $exchange = array_get($payload, 'exchange');
                $queue = array_get_or($payload, ['queue', 'topic']);
                $routingKey = array_get_or(
                    $payload,
                    ['routing_key', 'routing_keys', 'routing', 'binding_key', 'binding_keys', 'binding'],
                    ''
                );

                $pubPayload = [];
                if (!empty($channel)) {
                    $pubPayload['channel'] = $channel;
                }
                if (!empty($exchange)) {
                    $pubPayload['exchange'] = $exchange;
                } elseif (!empty($queue)) {
                    $pubPayload['queue'] = $queue;
                }
                if (!empty($routingKey)) {
                    $pubPayload['routing_key'] = $routingKey;
                }
                $pubPayload['message'] = BaseSubscriber::TERMINATOR;

                $this->parent->getClient()->publish($pubPayload);
            }
        }

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
                    $msg =
                        'No queue/topic/exchange and/or service information provided in subscription payload[' .
                        $i .
                        '].';
                } else {
                    $msg = 'No queue/topic/exchange and/or service information provided in subscription payload.';
                }

                throw new BadRequestException($msg);
            }
        }
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
                'get'    => [
                    'summary'     => 'Retrieves subscription/consumer job(s)',
                    'description' => 'Retrieves subscription/consumer job(s)',
                    'operationId' => 'get' . $capitalized . 'SubscriptionJobs',
                    'responses'   => [
                        '200' => [
                            'description' => 'Success',
                            'content'     => [
                                'application/json' => [
                                    'schema' => [
                                        'type'  => 'array',
                                        'items' => [
                                            'type'       => 'object',
                                            'required'   => ['sub', 'attempted'],
                                            'properties' => [
                                                'sub'       => [
                                                    'type'       => 'object',
                                                    'required'   => ['queue', 'service'],
                                                    'properties' => [
                                                        'exchange'    => SwaggerDefinitions::getExchangeDef(),
                                                        'queue'       => SwaggerDefinitions::getQueueDef(),
                                                        'qos'         => SwaggerDefinitions::getQosDef(),
                                                        'routing_key' => SwaggerDefinitions::getRoutingKeyDef(),
                                                        'service'     => SwaggerDefinitions::getServiceDef(),
                                                    ]
                                                ],
                                                'attempted' => [
                                                    'type'        => 'integer',
                                                    'description' => 'Indicates whether consumer process was started (1) or not (0)'
                                                ],
                                            ],
                                        ],

                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
                'post'   => [
                    'summary'     => 'Creates subscriber/consumer job(s)',
                    'description' => 'Creates subscriber/consumer job(s)',
                    'operationId' => 'subscribeTo' . $capitalized . 'Jobs',
                    'requestBody' => [
                        'description' => 'Subscriber(s)/Consumer(s) details',
                        'content'     => [
                            'application/json' => [
                                'schema' => [
                                    'type'  => 'array',
                                    'items' => [
                                        'type'       => 'object',
                                        'required'   => ['queue', 'service'],
                                        'properties' => [
                                            'exchange'    => SwaggerDefinitions::getExchangeDef(),
                                            'queue'       => SwaggerDefinitions::getQueueDef(),
                                            'qos'         => SwaggerDefinitions::getQosDef(),
                                            'routing_key' => SwaggerDefinitions::getRoutingKeyDef(),
                                            'service'     => SwaggerDefinitions::getServiceDef(),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'required'    => true
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/Success']
                    ],
                ],
                'delete' => [
                    'summary'     => 'Terminate subscription job(s)',
                    'description' => 'Terminate subscription job(s)',
                    'operationId' => 'terminatesSubscriptionsTo' . $capitalized,
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/Success']
                    ],
                ]
            ]
        ];

        return $base;
    }
}