<?php
return
    [
        'routing' => [
            'value' => [
                'config' => [
                    'api.php',
                ],
            ],
            'readonly' => false,
        ],
        'exception_handling' =>
            array(
                'value' =>
                    array(
                        'debug' => true,
                        'handled_errors_types' => 4437,
                        'exception_errors_types' => 4437,
                        'ignore_silence' => false,
                        'assertion_throws_exception' => true,
                        'assertion_error_type' => 256,
                        'log' => NULL,
                    ),
                'readonly' => false,
            ),

    ];