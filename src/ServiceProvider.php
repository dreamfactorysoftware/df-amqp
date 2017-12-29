<?php

namespace DreamFactory\Core\AMQP;

use DreamFactory\Core\AMQP\Models\AMQPConfig;
use DreamFactory\Core\AMQP\Services\AMQP;
use DreamFactory\Core\Enums\LicenseLevel;
use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df){
            $df->addType(
                new ServiceType([
                    'name'                  => 'amqp',
                    'label'                 => 'AMQP Client',
                    'description'           => 'AMQP Client service for DreamFactory',
                    'group'                 => ServiceTypeGroups::IOT,
                    'subscription_required' => LicenseLevel::SILVER,
                    'config_handler'        => AMQPConfig::class,
                    'factory'               => function ($config){
                        return new AMQP($config);
                    },
                ])
            );
        });
    }

    public function boot()
    {
        // add migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}