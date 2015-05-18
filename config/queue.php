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

            'queues_params'   => [
                'fill_matches' => [
                    'arguments' => [
                        'x-max-priority' => ['s', 255], // https://www.rabbitmq.com/priority.html
                    ],
                ],
                'test_priority' => [
                    'arguments' => [
                        'x-max-priority' => ['s', 255], // https://www.rabbitmq.com/priority.html
                    ],
                ],
            ],

            'exchange_params' => [
                'type'        => env('RABBITMQ_EXCHANGE_TYPE', 'direct'),
                'passive'     => env('RABBITMQ_EXCHANGE_PASSIVE', false),
                'durable'     => env('RABBITMQ_EXCHANGE_DURABLE', true),
                'auto_delete' => env('RABBITMQ_EXCHANGE_AUTODELETE', false),
            ],
        ],
    ],
];
