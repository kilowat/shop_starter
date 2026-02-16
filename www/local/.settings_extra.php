<?php
return [
    'composer' => [
        'value' => [
            'config_path' => 'www/local/composer.json'
        ]
    ],
    'routing' => [
        'value' => [
            'config' => [
                'auth.php',
            ],
        ],
        'readonly' => false,
    ],
    'exception_handling' => [
        'value' => [
            'debug' => true,
            'handled_errors_types' => 4437,
            'exception_errors_types' => 4437,
            'ignore_silence' => false,
            'assertion_throws_exception' => true,
            'assertion_error_type' => 256,
            'log' => null,
        ],
        'readonly' => false,
    ],
];