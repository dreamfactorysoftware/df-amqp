<?php

namespace DreamFactory\Core\AMQP\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;

class AMQPConfig extends BaseServiceConfigModel
{
    /** @var string  */
    protected $table = 'amqp_config';

    /** @var array  */
    protected $fillable = [
        'service_id',
        'host',
        'port',
        'username',
        'password',
        'vhost'
    ];

    /** @var array  */
    protected $casts = [
        'service_id' => 'integer',
        'port'       => 'integer'
    ];

    /** @var array */
    protected $encrypted = ['password'];

    /** @var array */
    protected $protected = ['password'];

    /**
     * {@inheritdoc}
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'host':
                $schema['label'] = 'Broker Host';
                $schema['description'] = 'Host name or IP address of your AMQP broker.';
                break;
            case 'port':
                $schema['label'] = 'Broker Port';
                $schema['default'] = 5672;
                $schema['description'] = 'Port number of your AMQP broker. Default is 5672.';
                break;
            case 'username':
                $schema['label'] = 'Username';
                $schema['description'] = 'Provide username if your broker requires authentication.';
                break;
            case 'password':
                $schema['type'] = 'password';
                $schema['label'] = 'Password';
                $schema['description'] = 'Provide password for your username if your broker requires authentication.';
                break;
            case 'vhost':
                $schema['label'] = 'Virtual Host';
                $schema['default'] = '/';
                $schema['description'] = "Provide your virtual hostname. Default is '/'";
                break;
        }
    }
}