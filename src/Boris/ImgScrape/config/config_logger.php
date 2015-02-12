<?php
return [
    'enabled'  => true,
    'handlers' => [
        [
            'dir'   => __DIR__ . '/../../../../log/debug.log',
            'level' => 'debug'
        ],
        [
            'dir'   => __DIR__ . '/../../../../log/main.log',
            'level' => 'info'
        ],
    ]
];
