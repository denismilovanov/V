<?php

return [
    'default' => 'rabbitmq',
    'connections' => [
        'rabbitmq' => [
            'driver'          => 'rabbitmq',

            'host'            => env('RABBITMQ_HOST', '127.0.0.1'),
            'port'            => env('RABBITMQ_PORT', 5672),

            'vhost'           => env('RABBITMQ_VHOST', '/'),
            'login'           => env('RABBITMQ_LOGIN', 'guest'),
            'password'        => env('RABBITMQ_PASSWORD', 'guest'),

            'queue'           => env('RABBITMQ_QUEUE'), // name of the default queue,

            'queue_params'    => [
                'passive'     => env('RABBITMQ_QUEUE_PASSIVE', false),
                'durable'     => env('RABBITMQ_QUEUE_DURABLE', true),
                'exclusive'   => env('RABBITMQ_QUEUE_EXCLUSIVE', false),
                'auto_delete' => env('RABBITMQ_QUEUE_AUTODELETE', false),
            ],

            'exchange_params' => [
                'type'        => env('RABBITMQ_EXCHANGE_TYPE', 'direct'), // more info at http://www.rabbitmq.com/tutorials/amqp-concepts.html
                'passive'     => env('RABBITMQ_EXCHANGE_PASSIVE', false),
                'durable'     => env('RABBITMQ_EXCHANGE_DURABLE', true), // the exchange will survive server restarts
                'auto_delete' => env('RABBITMQ_EXCHANGE_AUTODELETE', false),
            ],
        ],
    ],
];
