<?php

namespace DreamFactory\Core\AMQP\Services;

use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Utility\Session;

abstract class BaseService extends BaseRestService
{
    /** @var  \DreamFactory\Core\AMQP\Contracts\AMQPInterface */
    protected $client;

    public function __construct(array $settings)
    {
        parent::__construct($settings);

        $config = array_get($settings, 'config');
        Session::replaceLookups($config, true);
        $this->setClient($config);
    }

    protected abstract function setClient($config);
}