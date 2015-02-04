<?php

return ['imageLinksOnly' => true,
    'acceptedTypes' => [
        'jpeg',
        'jpg',
        'gif',
        'png',
    ],
    'logger' => [
        'enabled' => true,
        'handlers' => [
            [
                'dir' => __DIR__ . '/../../../../log/main.log',
                'level' => 'info'
            ],
            [
                'dir' => __DIR__ . '/../../../../log/debug.log',
                'level' => 'debug'
            ]
        ]
    ]
];
