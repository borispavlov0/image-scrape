<?php

return [
    'imageLinksOnly' => false,
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
                'dir' => __DIR__ . '/../../../../log/debug.log',
                'level' => 'debug'
            ],
            [
                'dir' => __DIR__ . '/../../../../log/main.log',
                'level' => 'info'
            ],
        ]
    ],
    'blacklist' => [
        'www.reddit.com'
    ]
];
